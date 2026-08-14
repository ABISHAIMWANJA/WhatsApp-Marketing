(function($) {
    "use strict";
    var fields = [];
    var patternToSearchInputField = /\{\{(\d+)\}\}/g;
    const el = document.getElementById('lwNewTemplateData');
    $("#lwTemplateBody").on("input", function() {
        // Get the input value
        var inputValue = $(this).val();
        updatePlaceholders(inputValue, 'lwTemplateBody', 'headerTemplate');
    });

    $('#lwAddPlaceHolder').click(function() {
        addNewPlaceholder('lwTemplateBody', 'headerTemplate');
    });
    $("#lwHeaderTextBody").on("input", function() {
        // Get the input value
        var inputValue = $(this).val();
        var matches = inputValue.match(patternToSearchInputField);
        if(matches) {
            __DataRequest.updateModels({enableHeaderVariableExample : true});
        } else {
            __DataRequest.updateModels({enableHeaderVariableExample : false});
        }
    });
    $('#lwAddSinglePlaceHolder').click(function() {
        let headerTextBody= $('#lwHeaderTextBody');
        let currentText = headerTextBody.val();
        let cursorPos = headerTextBody.prop('selectionStart');
        let beforeText = currentText.substring(0, cursorPos);
        let afterText = currentText.substring(cursorPos, currentText.length);
        let newText = beforeText + ` {{1}}` + afterText;
        headerTextBody.val(newText);
        $('#lwHeaderTextBody').trigger('input');
        __DataRequest.updateModels({header_text_body:newText});
    });

    $('#lwBoldBtn').click(function() {
        wrapWithItem('*', 'lwTemplateBody', 'headerTemplate');
    });
    $('#lwItalicBtn').click(function() {
        wrapWithItem('_', 'lwTemplateBody', 'headerTemplate');
    });
    $('#lwStrikeThroughBtn').click(function() {
        wrapWithItem('~', 'lwTemplateBody', 'headerTemplate');
    });
    $('#lwCodeBtn').click(function() {
        wrapWithItem('```', 'lwTemplateBody', 'headerTemplate');
    });

    window.updatePlaceholders = function(text, targetId, whatsappTemplateType) {
        const placeholderRegex = /\{\{\d+\}\}/g;
        let newText = updateSequence(text, placeholderRegex);
        $('#'+targetId).val(newText);
        let element = document.getElementById(targetId);
        var res = {};
        var matches = newText.match(patternToSearchInputField);
        if (matches) {
            for (let i = 0; i < matches.length; i++) {
                var newArr = {
                    'text_variable': matches[i],
                    'text_variable_value': matches[i],
                };
                res[matches[i].replace(/\{\{(\d+)\}\}/g, '$1')] = newArr;
                if (whatsappTemplateType == 'headerTemplate') {
                    // Your code to handle each matched pattern goes here
                    __DataRequest.updateModels({newBodyTextInputFields : res});
                } else if (whatsappTemplateType == 'carouselTemplate') {
                    __DataRequest.updateModels({carouselBodyTextVariables : res});
                } else if (_.isObject(whatsappTemplateType) && whatsappTemplateType.type == 'carouselCard') {
                    Alpine.$data(el).carouselTemplateContainer[whatsappTemplateType.index]['bodyTextVariables'] = res;
                }
            }
        } else {        
            if (whatsappTemplateType == 'headerTemplate') {
                __DataRequest.updateModels({newBodyTextInputFields : res});
            } else if (whatsappTemplateType == 'carouselTemplate') {
                __DataRequest.updateModels({carouselBodyTextVariables : res});
            } else if (_.isObject(whatsappTemplateType) && whatsappTemplateType.type == 'carouselCard') {
                Alpine.$data(el).carouselTemplateContainer[whatsappTemplateType.index]['bodyTextVariables'] = res;
            }
        }
    }

window.addNewPlaceholder = function(targetId, whatsappTemplateType) {
    let textarea = $('#'+targetId);
    let currentText = textarea.val();
    let cursorPos = textarea.prop('selectionStart');
    const placeholderRegex = /\{\{\d+\}\}/g;
    let matches = currentText.match(placeholderRegex) || [];
    let maxNumber = 0;
    matches.forEach(function(item) {
        const currentNumber = parseInt(item.match(/\d+/)[0], 10);
        if (currentNumber > maxNumber) {
            maxNumber = currentNumber;
        }
    });
    // Insert the new placeholder at the current cursor position
    let beforeText = currentText.substring(0, cursorPos);
    let afterText = currentText.substring(cursorPos, currentText.length);
    let newText = beforeText + ` {{${maxNumber + 1}}} ` + afterText;
    textarea.val(newText);
    // Place cursor right after the newly added placeholder
    let newPos = cursorPos + ` {{${maxNumber + 1}}} `.length;
    textarea[0].selectionStart = textarea[0].selectionEnd = newPos;
    textarea.focus(); // refocus the textarea after manipulation
    $('#'+targetId).trigger('input');
    if (whatsappTemplateType == 'headerTemplate') {
        __DataRequest.updateModels({text_body:newText});
    } else if (whatsappTemplateType == 'carouselTemplate') {
        __DataRequest.updateModels({carousel_body_text:newText});        
    } else if (_.isObject(whatsappTemplateType) && whatsappTemplateType.type == 'carouselCard') {
        Alpine.$data(el).carouselTemplateContainer[whatsappTemplateType.index]['bodyText'] = newText;
    }
}

window.wrapWithItem = function(wrapWith, targetId, whatsappTemplateType) {
        let $textarea = $('#'+targetId);
        let start = $textarea[0].selectionStart;
        let end = $textarea[0].selectionEnd;
        let selectedText = $textarea.val().substring(start, end);
        let beforeText = $textarea.val().substring(0, start);
        let afterText = $textarea.val().substring(end);
        let newText = beforeText + wrapWith + selectedText + wrapWith + afterText;
        $textarea.val(newText);
        // Update the cursor to be at the end of the newly wrapped text
        $textarea[0].selectionStart = $textarea[0].selectionEnd = start + selectedText.length + 2;
        $textarea.focus(); // Refocus the textarea after manipulation
        $('#'+targetId).trigger('input');
        if (whatsappTemplateType == 'headerTemplate') {
            __DataRequest.updateModels({text_body:newText});
        } else if (whatsappTemplateType == 'carouselTemplate') {
            __DataRequest.updateModels({carousel_body_text:newText}); 
        } else if (_.isObject(whatsappTemplateType) && whatsappTemplateType.type == 'carouselCard') {
            Alpine.$data(el).carouselTemplateContainer[whatsappTemplateType.index]['bodyText'] = newText;
        }
    }

    function updateSequence(text, regex) {
        let matches = text.match(regex);
        let unique = [];
        if (matches) {
            $.each(matches, function(i, el) {
                if ($.inArray(el, unique) === -1) unique.push(el);
            });

            unique.sort((a, b) => Number(a.match(/\d+/)[0]) - Number(b.match(/\d+/)[0]));
            const newNumbers = unique.reduce((acc, cur, index) => {
                const num = cur.match(/\d+/)[0];
                acc[num] = index + 1;
                return acc;
            }, {});
            return text.replace(regex, function(match) {
                const num = match.match(/\d+/)[0];
                return `{{${newNumbers[num]}}}`;
            });
        }
        return text;
    };

    window.scrollSlide = function(button, next = true) {
        const wrapper = button.closest('.lw-carousel-wrapper');
        const container = wrapper.querySelector('.lw-carousel-container');
        const cards = container.querySelectorAll('.lw-carousel-card');
        
        const card = cards[0];
        const scrollAmount = card.offsetWidth + 12; // card width + gap
        container.scrollBy({ 
            left: next ? scrollAmount : -scrollAmount, 
            behavior: 'smooth' 
        });
    }

/* 
 * Carousel Related functions start here 
 * ------------------------------------------*/

// Carousel Template Body Textarea code start here
$("#lwCarouselTemplateBody").on("input", function() {
    // Get the input value
    var inputValue = $(this).val();
    updatePlaceholders(inputValue, 'lwCarouselTemplateBody', 'carouselTemplate');
});

$('#lwCarouselAddPlaceHolder').click(function() {
    addNewPlaceholder('lwCarouselTemplateBody', 'carouselTemplate');
});

$('#lwCarouselBoldBtn').click(function() {
    wrapWithItem('*', 'lwCarouselTemplateBody', 'carouselTemplate');
});
$('#lwCarouselItalicBtn').click(function() {
    wrapWithItem('_', 'lwCarouselTemplateBody', 'carouselTemplate');
});
$('#lwCarouselStrikeThroughBtn').click(function() {
    wrapWithItem('~', 'lwCarouselTemplateBody', 'carouselTemplate');
});
$('#lwCarouselCodeBtn').click(function() {
    wrapWithItem('```', 'lwCarouselTemplateBody', 'carouselTemplate');
});
// Carousel Template Body Textarea code end here

/* Carousel Related functions end here */

window.copyCodeToClipboard = function(copyText) {
    var copyApiCodeTextElement = document.getElementById('lwApiRequestCode').innerText,
        lwApiCodeCopyBtn = document.getElementById('lwApiCodeCopyBtn'),
        originalText = lwApiCodeCopyBtn.innerHTML;
    
    navigator.clipboard.writeText(copyApiCodeTextElement)
        .then(() => {
            lwApiCodeCopyBtn.innerHTML = '<i class="fas fa-check-double"></i> '+copyText;
            lwApiCodeCopyBtn.disabled = true;

            setTimeout(() => {
                lwApiCodeCopyBtn.innerHTML = originalText;
                lwApiCodeCopyBtn.disabled = false;
            }, 2000);
        })
        .catch(err => {
            console.error('Copy failed', err);
        });
}
})(jQuery);