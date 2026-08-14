<?php

/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */

/**
 * OpenAiService.php -
 *
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Services;

// use OpenAI;
use Exception;
use OpenAI\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;

class OpenAiService extends BaseEngine
{
    protected function initConfiguration($vendorId = null, $accessKey = null, $orgKey = null)
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }
        config([
            'openai.api_key' =>  $accessKey ?: getVendorSettings('open_ai_access_key', null, null, $vendorId),
            'openai.organization' => $orgKey ?: getVendorSettings('open_ai_organization_id', null, null, $vendorId),
        ]);
    }
    /**
     * Generate embeddings for large data and store it in the database.
     */
    public function embedLargeData($largeData, $options = [])
    {
        $options  = array_merge([
            'open_ai_access_key' => null,
            'open_ai_organization_id' => null
        ], $options);
        $this->initConfiguration(null, $options['open_ai_access_key'], $options['open_ai_organization_id']);
        // Step 1: Split the large data into meaningful chunks
        $sections = $this->splitDataIntoChunks($largeData);

        // Step 2: Generate embeddings for each section
        $embeddings = [];
        foreach ($sections as $section) {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $section,
            ]);

            $embeddings[] = $response['data'][0]['embedding'];
        }

        // Step 3: Store the data and embeddings in the database
        return [
            'data' => $sections,
            'embedding' => $embeddings,
        ];
    }

    /**
     * Split the large dataset into smaller meaningful chunks.
     */
    private function splitDataIntoChunks($data, $maxChunkSize = 500)
    {
        $chunks = [];
        $currentChunk = '';
        $sentences = preg_split('/(?<=[.?!])\s+/', $data);  // Split by sentences

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk . ' ' . $sentence) > $maxChunkSize) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $sentence;
            } else {
                $currentChunk .= ' ' . $sentence;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Embed the user's question.
     */
    private function embedQuestion($question)
    {
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $question,
        ]);

        return $response['data'][0]['embedding'];
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    private function cosineSimilarity($vecA, $vecB)
    {
        $dotProduct = array_sum(array_map(function ($a, $b) {
            return $a * $b;
        }, $vecA, $vecB));

        $magnitudeA = sqrt(array_sum(array_map(function ($a) {
            return $a ** 2;
        }, $vecA)));

        $magnitudeB = sqrt(array_sum(array_map(function ($b) {
            return $b ** 2;
        }, $vecB)));

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    /**
     * Find the most relevant section based on the user's question.
     */
    private function findRelevantSection($question, $vendorId)
    {
        $this->initConfiguration($vendorId);
        // Step 1: Embed the question
        $questionEmbedding = $this->embedQuestion($question);

        // Step 2: Fetch the large dataset and embeddings from the database
        // $largeDataRecord = LargeData::first();
        // $sections = preg_split('/\n\n+/', $largeDataRecord->data);
        // $storedEmbeddings = json_decode($largeDataRecord->embedding);
        $largeDataRecord = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
        $sections = $largeDataRecord['data']; //preg_split('/\n\n+/', $largeDataRecord['data']);  // Ensure you split the data in the same way
        $storedEmbeddings = ($largeDataRecord['embedding']);

        // Step 3: Compare the embeddings
        $similarities = [];
        foreach ($storedEmbeddings as $index => $sectionEmbedding) {
            $similarity = $this->cosineSimilarity($questionEmbedding, $sectionEmbedding);
            $similarities[] = [
                'section' => $sections[$index],
                'similarity' => $similarity,
            ];
        }

        // Step 4: Sort by similarity and return the top section
        usort($similarities, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $similarities[0]['section'];
    }

    /**
     * Find the top N relevant sections for broader context.
     */
    private function findTopRelevantSections($question, $vendorId, $topN = 3)
    {
        $this->initConfiguration($vendorId);
        $questionEmbedding = $this->embedQuestion($question);
        // $largeDataRecord = LargeData::first();
        // $sections = preg_split('/\n\n+/', $largeDataRecord->data);
        // $storedEmbeddings = json_decode($largeDataRecord->embedding);
        $largeDataRecord = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
        $sections = $largeDataRecord['data']; //preg_split('/\n\n+/', $largeDataRecord['data']);  // Ensure you split the data in the same way
        $storedEmbeddings = ($largeDataRecord['embedding']);
        $similarities = [];
        foreach ($storedEmbeddings as $index => $sectionEmbedding) {
            $similarity = $this->cosineSimilarity($questionEmbedding, $sectionEmbedding);
            $similarities[] = [
                'section' => $sections[$index],
                'similarity' => $similarity,
            ];
        }

        usort($similarities, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice($similarities, 0, $topN);
    }

    /**
     * Generate an answer using the most relevant section.
     */
    public function generateAnswerFromSingleSection($question, $vendorId)
    {
        // Step 1: Find the most relevant section
        $relevantSection = $this->findRelevantSection($question, $vendorId);
        $botName  = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
        // Step 2: Use OpenAI completion API to generate a refined answer
        $response = OpenAI::completions()->create([
            'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a helpful assistant that generates well-formatted answers." . ($botName ? ' your name is ' . $botName : ''),
                ],
                [
                    'role' => 'user',
                    'content' => "Based on the following content, answer the question in a well-formatted, structured way with appropriate new lines and paragraphs:\n\nContent: {$relevantSection}\n\nQuestion: {$question}",
                ]
            ],
            'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
        ]);

        return trim($response['choices'][0]['text']);
    }

    /**
     * Generate an answer by combining multiple relevant sections for broader context.
     */
    public function generateAnswerFromMultipleSections($question, $contact, $vendorId)
    {
        $contactUid = $contact->_uid;
        $botName = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
        $botDataSourceType = getVendorSettings('open_ai_bot_data_source_type', null, null, $vendorId);
        $useExistingChatHistory = getVendorSettings('use_existing_chat_history', null, null, $vendorId);
        if ($botDataSourceType == 'assistant') {
            $this->initConfiguration($vendorId);

            $messages = [
                [
                    'role' => 'assistant',
                    'content' => "You are a helpful assistant " . ($botName ? ' your name is ' . $botName . ' and don"t include your name in reply.' : '') . " a well-formatted, structured way with appropriate new lines and paragraphs. Strictly do not answer out of given context, your answer should be based on the given context and content. You are talking with " . $contact->full_name ?: '',
                ]
            ];

            // Check if use existing chat history to message smartly
            if ($useExistingChatHistory) {
                $existingHistoryData = array_filter(
                    array_reverse($this->getExistingChatHistory($contactUid)),
                    fn($msg) => !empty(trim($msg['content'] ?? ''))
                );
                $messages = array_merge($messages, array_slice($existingHistoryData, 0, 30));
            }

            $messages[] = [
                'role' => 'user',
                'content' => $question
            ];
            $threadRun = $response = OpenAI::threads()->createAndRun([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
                'assistant_id' => getVendorSettings('open_ai_assistant_id', null, null, $vendorId),
                'thread' => [
                    'messages' => $messages,
                ],
            ]);
            while (in_array($threadRun->status, ['queued', 'in_progress'])) {
                $threadRun = OpenAI::threads()->runs()->retrieve(
                    threadId: $threadRun->threadId,
                    runId: $threadRun->id,
                );
            }
            if ($threadRun->status !== 'completed') {
                return getVendorSettings('open_ai_failed_message', null, null, $vendorId) ?: 'Request failed, please try again';
            }
            $messageList = OpenAI::threads()->messages()->list(
                threadId: $threadRun->threadId,
            );
            return $messageList->data[0]->content[0]->text->value;
        }
        // Text Based Source type
        // Step 1: Find the top relevant sections
        $topSections = $this->findTopRelevantSections($question, $vendorId);
        $combinedSections = implode("\n\n", array_column($topSections, 'section'));

        $messages = [
            [
                'role' => 'system',
                'content' => ($botName ? ' your name is ' . $botName : '') . '. You are a smart AI agent that continues helpful, coherent conversations based on the full context. If the question is out of context tell the user that its out of scope. Strictly do not answer out of given context, your answer should be based on the given context and content. Based on the following content, answer the question in a well-formatted, structured way with appropriate new lines and paragraphs:\n\nContent: ' . $combinedSections . " You are talking with " . $contact->full_name ?: '',
            ]
        ];

        // Check if use existing chat history to message smartly
        if ($useExistingChatHistory) {
            $existingHistoryData = $this->getExistingChatHistory($contactUid);
            $messages = array_merge($messages, $existingHistoryData);
        }

        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];

        // Step 2: Use OpenAI completion API to generate a refined answer
        try {
            $response = OpenAI::chat()->create([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
                'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
                'temperature' => 0.7,
                'messages' => $messages
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        return trim($response['choices'][0]['message']['content']);
    }

    protected function getExistingChatHistory($contactUid)
    {
        $contact = ContactModel::where('_uid', $contactUid)->first();
        $vendorId = $contact->vendors__id;

        $defaultRecentMessageCount = 6;
        $pastSummary = data_get($contact, '__data.past_ai_summary');
        // check if existing API summary not exists
        if (__isEmpty($pastSummary)) {
            $defaultRecentMessageCount = 30;
        }

        $whatsAppMessageLogCollection = WhatsAppMessageLogModel::where('contacts__id', $contact->_id)
            ->whereNotNull('message')
            ->whereNull('is_system_message')
            ->take($defaultRecentMessageCount)
            ->latest()
            ->get();

        $recentMessages = [];
        // Check if existing chat history exists
        if (!__isEmpty($whatsAppMessageLogCollection)) {
            foreach ($whatsAppMessageLogCollection as $existingChat) {
                $recentMessages[] = [
                    'role' => $existingChat->is_incoming_message ? 'user' : 'assistant',
                    'content' => $existingChat->message
                ];
            }
        }

        $messages = [
            [
                'role' => 'assistant',
                'content' => "You are a summarizer. Combine the existing summary below with the new conversation to form a short updated memory. Be concise and store important facts only."
            ]
        ];

        $existingSummary = [];
        if (!__isEmpty($pastSummary)) {
            $existingSummary = [
                'role' => 'user',
                'content' => "Existing summary: " . $pastSummary
            ];
            
            $messages[] = $existingSummary;
        }

        $messages[] = [
            'role' => 'user',
            'content' => "New conversation: " . json_encode($recentMessages)
        ];

        $messages = array_filter($messages, function ($msg) {
            return isset($msg['content']) && trim($msg['content']) !== '';
        });

        // Step 2: Use OpenAI completion API to generate a refined answer
        try {
            $response = OpenAI::chat()->create([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
                'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
                'temperature' => 0.7,
                'messages' => $messages
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        
        $newNewSummary = trim($response['choices'][0]['message']['content']);

        $contactRepository = new ContactRepository();
        $contactRepository->updateIt($contact, [
            '__data' => [
                'past_ai_summary' => $newNewSummary
            ]
        ]);

        return (!__isEmpty($existingSummary)) ? array_merge([$existingSummary], $recentMessages) : $recentMessages;
    }
}
