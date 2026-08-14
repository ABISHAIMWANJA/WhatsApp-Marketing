<!-- Send Recording Modal -->
<x-lw.modal id="lwSendRecording" :header="__tr('Send Recording')" :hasForm="true"
    data-pre-callback="appFuncs.clearContainer">

    <x-lw.form id="lwRecordedMediaForm" :action="route('vendor.chat_message_media.send.process')"
        data-callback="appFuncs.modelSuccessCallback" :data-callback-params="['modalId' => '#lwSendRecording']">

        <div id="lwRecordingModal" class="lw-form-modal-body"></div>

        <input id="lwUploadedFileName" type="hidden" value="" name="uploaded_media_file_name"/>
        <input type="hidden" name="contact_uid" x-bind:value="contact?._uid">
        <input type="hidden" value="audio" name="media_type"/>
        <input type="hidden" value="true" name="is_recorded_audio"/>
        <div class="text-center">
        <div id="recorder-container" class="lw-recording-container">
        <h1 class="lw-recorder-title">{!! __tr('Record & Send') !!}</h1>
        <p class="lw-recording-message" id="statusMessage">{{ __tr('Click to start recording.') }}</p>

        <div class="d-flex justify-content-center align-items-center my-4">
            <!-- Record Button -->
            <button id="startButton" class="lw-start-recording-button btn btn-danger rounded-circle d-flex align-items-center justify-content-center me-4">
                <i class="fas fa-5x fa-microphone fa-lg"></i>
            </button>

            <!-- Stop Button -->
            <button id="stopButton" class="lw-stop-recording-button btn btn-dark rounded-circle d-flex align-items-center justify-content-center">
                <i class="fas fa-5x fa-stop fa-lg"></i>
            </button>
        </div>

          <div class="text-center">
            <h1 id="lwTimeCounter" class="lw-recorder-counter"></h1>
          </div>
          <div id="playbackContainer" style="display:none;">
            <audio class="lw-recorded-audio" id="audioPlayback" controls></audio>
            <br>
            <a class="d-none" id="downloadLink">{{  __tr('Download MP3') }}</a>
          </div>
          <button id="lwSendAudioButton" type="submit" class="btn btn-lg btn-primary"> <i class="fa fa-paper-plane"></i> {{ __tr('Send') }}</button>
        </div>
        </div>
        <!-- form footer --> 
        <div class="modal-footer">
            <button id="lwCloseModalButton" type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
        <!-- /form footer --> 
    </x-lw.form>
</x-lw.modal>
<!--/ Send Recording Modal -->

@push('appScripts')
<script type="module" src="https://unpkg.com/vmsg@0.4.0/vmsg.js"></script>

<script type="module">
    // This script will only run in browsers that support ES modules.
    import vmsg from "https://unpkg.com/vmsg@0.4.0/vmsg.js";

    const startButton = document.getElementById('startButton');
    const stopButton = document.getElementById('stopButton');
    const audioPlayback = document.getElementById('audioPlayback');
    const downloadLink = document.getElementById('downloadLink');
    const playbackContainer = document.getElementById('playbackContainer');
    const statusMessage = document.getElementById('statusMessage');
    const sendAudioButton = document.getElementById('lwSendAudioButton');
    sendAudioButton.disabled = true;

    let timerInterval;
    let seconds = 0;
    let isRecording = false;
    var counterEl = document.getElementById("lwTimeCounter");

    const recorder = new vmsg.Recorder({
        wasmURL: "https://unpkg.com/vmsg@0.4.0/vmsg.wasm"
    });

    // Set stop button disable by default
    stopButton.disabled = true;
    $('#stopButton').addClass('lw-force-hide');
    const checkCompatibility = () => {
        // Check for necessary APIs
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder && window.WebAssembly) {
            return true;
        }
        // If not supported, update the UI
        statusMessage.textContent = '{{ __tr("Sorry, your browser does not support the required APIs for audio recording.") }}';
        startButton.disabled = true;
        stopButton.disabled = true;
        $('#stopButton').addClass('lw-force-hide');
        return false;
    };

    const startCounter = () => {
        seconds = 0;
        counterEl.style.display = "block";
        sendAudioButton.disabled = true;
        counterEl.textContent = "⏱ " + "{{ __tr('00:00') }}";
        timerInterval = setInterval(() => {
            seconds++;
            const mins = String(Math.floor(seconds / 60)).padStart(2, "0");
            const secs = String(seconds % 60).padStart(2, "0");
            counterEl.textContent = `⏱ ${mins}:${secs}`;
        }, 1000);
    };

    const stopCounter = () => {
        clearInterval(timerInterval);
        counterEl.style.display = "none";
        sendAudioButton.disabled = false;
    };
    
    const record = async () => {
        statusMessage.textContent = '{{ __tr("Recording...") }}';
        startButton.disabled = true;
        stopButton.disabled = false;
        $('#startButton').addClass('lw-force-hide');
        $('#stopButton').removeClass('lw-force-hide');
        playbackContainer.style.display = 'none';
        isRecording = true;

        try {
            await recorder.initAudio();
            await recorder.initWorker();
            recorder.startRecording();
            startCounter();
        } catch (e) {
            console.error(e);
            statusMessage.textContent = '{{ __tr("Please grant microphone permission.") }}';
            startButton.disabled = false;
            stopButton.disabled = true;
            stopCounter();
        }
    };

    const stop = async () => {
        statusMessage.textContent = '{{ __tr("Encoding audio... please wait.") }}';
        startButton.disabled = false;
        stopButton.disabled = true;
        $('#startButton').removeClass('lw-force-hide');
        $('#stopButton').addClass('lw-force-hide');
        isRecording = false;
        stopCounter();

        try {
            const mp3Blob = await recorder.stopRecording();
            const url = URL.createObjectURL(mp3Blob);
            uploadAudioFile(mp3Blob);
            
            audioPlayback.src = url;
            downloadLink.href = url;
            downloadLink.download = `recording-${new Date().toISOString()}.mp3`;
            
            playbackContainer.style.display = 'block';
            downloadLink.style.display = 'inline-block';
            statusMessage.textContent = '{{ __tr("Recording finished. Ready for playback or send.") }}';
            sendAudioButton.disabled = false;
        } catch (e) {
            console.error(e);
            statusMessage.textContent = '{{ __tr("Error encoding audio") }}';
        }
    };
    
    // Initialize the app
    if (checkCompatibility()) {
        startButton.addEventListener('click', record);
        stopButton.addEventListener('click', stop);
    }

    function uploadAudioFile(audioFile) {
      const formData = new FormData();
      const fileName = `recording-${new Date().toISOString()}.mp3`;
      formData.append('filepond', audioFile, fileName);
      formData.append("_token", "{{ csrf_token() }}");
      fetch("{{ route('media.upload_temp_media', ['uploadItem' => 'whatsapp_audio']) }}", {
          method: "POST",
          body: formData
      })
      .then(res => res.json())
      .then(responseData => {
        const uploadedFileName = document.getElementById('lwUploadedFileName');
        uploadedFileName.value = responseData.data.fileName;
      })
      .catch(err => console.error(err));
    };

    $('#lwSendRecording').on('hidden.bs.modal', async function (hiddenEvent) {
        audioPlayback.pause();
        audioPlayback.removeAttribute('src'); // unload
        audioPlayback.load();
        playbackContainer.style.display = 'none';
        counterEl.textContent = '';
        counterEl.style.display = "none";
        statusMessage.textContent = '{{ __tr("Click Start to begin recording") }}';
        startButton.disabled = false;
        stopButton.disabled = true;
        sendAudioButton.disabled = true;
        $('#startButton').removeClass('lw-force-hide');
        $('#stopButton').addClass('lw-force-hide');
        clearInterval(timerInterval);
        if (isRecording) {
            const blob = await recorder.stopRecording();
            isRecording = false;
        }
    });
</script>
@endpush