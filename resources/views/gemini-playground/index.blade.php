<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gemini Audio Testing Playground</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
        .spinner {
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            width: 1.5rem;
            height: 1.5rem;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Navigation -->
        <nav class="flex space-x-4 mb-6 border-b border-gray-200 pb-4">
            <a href="/gemini-playground" class="text-blue-600 font-medium border-b-2 border-blue-600 pb-4 -mb-4">Free-form Playground</a>
            <a href="/gemini-tests" class="text-gray-500 hover:text-gray-700 font-medium">Audio Testing Suite</a>
        </nav>

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Gemini Audio Playground</h1>
            <p class="text-gray-600 mt-2">Test Gemini's native audio understanding by providing context and an audio recording.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column: Input Form -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <form id="geminiForm" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="context" class="block text-sm font-medium text-gray-700">Context / Instructions</label>
                        <textarea id="context" name="context" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-3" placeholder="You are an English pronunciation assessor. Please evaluate the following audio..."></textarea>
                    </div>

                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700">Gemini Model</label>
                        <select id="model" name="model" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                            @foreach($models as $model)
                                <option value="{{ $model['name'] }}">{{ $model['displayName'] }} ({{ $model['name'] }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Audio Input</label>
                        
                        <div class="flex items-center space-x-4">
                            <button type="button" id="recordBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                Start Recording
                            </button>
                            <button type="button" id="stopBtn" class="hidden inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition">
                                Stop Recording
                            </button>
                            <span id="recordingStatus" class="text-sm text-gray-500 hidden animate-pulse">Recording...</span>
                        </div>

                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span>OR</span>
                            <input type="file" id="audioUpload" accept="audio/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>

                        <div id="audioPreviewContainer" class="hidden mt-4 p-4 bg-gray-50 rounded-md border border-gray-200">
                            <p class="text-xs text-gray-500 mb-2">Selected Audio:</p>
                            <audio id="audioPlayback" controls class="w-full"></audio>
                            <button type="button" id="clearAudioBtn" class="mt-2 text-sm text-red-600 hover:text-red-800">Clear Audio</button>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200">
                        <button type="submit" id="submitBtn" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <span id="submitText">Test with Gemini</span>
                            <div id="submitSpinner" class="hidden spinner ml-3"></div>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Results Display -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex flex-col h-full min-h-[600px]">
                <h2 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-4">Gemini Response</h2>
                
                <div id="loadingState" class="hidden flex-1 flex flex-col items-center justify-center text-gray-500">
                    <div class="spinner border-blue-500 border-t-transparent w-8 h-8 mb-4"></div>
                    <p>Processing with Gemini...</p>
                </div>

                <div id="emptyState" class="flex-1 flex items-center justify-center text-gray-400">
                    Submit a test to see the response here.
                </div>

                <div id="errorState" class="hidden bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-medium" id="errorMessage"></p>
                        </div>
                    </div>
                </div>

                <div id="resultState" class="hidden flex-1 flex flex-col">
                    <div class="flex space-x-4 mb-4 text-xs">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-blue-100 text-blue-800">
                            Model: <span id="resModel" class="ml-1 font-bold"></span>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-green-100 text-green-800">
                            Time: <span id="resTime" class="ml-1 font-bold"></span>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-purple-100 text-purple-800">
                            Tokens: <span id="resTokens" class="ml-1 font-bold"></span>
                        </span>
                    </div>

                    <div class="flex-1 bg-gray-50 p-4 rounded-md overflow-y-auto prose prose-sm max-w-none border border-gray-200 text-gray-800" id="resText"></div>

                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none">View Raw API Response</summary>
                        <pre id="resRaw" class="mt-2 text-xs bg-gray-800 text-green-400 p-4 rounded-md overflow-x-auto"></pre>
                    </details>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let mediaRecorder;
            let audioChunks = [];
            let currentAudioBlob = null;
            let audioFormat = 'audio/webm'; // Default format

            const recordBtn = document.getElementById('recordBtn');
            const stopBtn = document.getElementById('stopBtn');
            const recordingStatus = document.getElementById('recordingStatus');
            const audioUpload = document.getElementById('audioUpload');
            const audioPreviewContainer = document.getElementById('audioPreviewContainer');
            const audioPlayback = document.getElementById('audioPlayback');
            const clearAudioBtn = document.getElementById('clearAudioBtn');
            
            const form = document.getElementById('geminiForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');

            const emptyState = document.getElementById('emptyState');
            const loadingState = document.getElementById('loadingState');
            const errorState = document.getElementById('errorState');
            const errorMessage = document.getElementById('errorMessage');
            const resultState = document.getElementById('resultState');

            // --- Audio Recording Logic ---
            recordBtn.addEventListener('click', async () => {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);
                    
                    mediaRecorder.ondataavailable = (event) => {
                        if (event.data.size > 0) {
                            audioChunks.push(event.data);
                        }
                    };

                    mediaRecorder.onstop = () => {
                        currentAudioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                        audioFormat = mediaRecorder.mimeType || 'audio/webm';
                        const audioUrl = URL.createObjectURL(currentAudioBlob);
                        
                        audioPlayback.src = audioUrl;
                        audioPreviewContainer.classList.remove('hidden');
                        audioUpload.value = ''; // clear file upload
                        
                        // stop tracks to release mic
                        stream.getTracks().forEach(track => track.stop());
                    };

                    audioChunks = [];
                    mediaRecorder.start();
                    
                    recordBtn.classList.add('hidden');
                    stopBtn.classList.remove('hidden');
                    recordingStatus.classList.remove('hidden');
                } catch (err) {
                    alert('Microphone access denied or not available: ' + err.message);
                }
            });

            stopBtn.addEventListener('click', () => {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                }
                stopBtn.classList.add('hidden');
                recordBtn.classList.remove('hidden');
                recordingStatus.classList.add('hidden');
            });

            // --- File Upload Logic ---
            audioUpload.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    currentAudioBlob = file;
                    const audioUrl = URL.createObjectURL(file);
                    audioPlayback.src = audioUrl;
                    audioPreviewContainer.classList.remove('hidden');
                }
            });

            clearAudioBtn.addEventListener('click', () => {
                currentAudioBlob = null;
                audioPlayback.src = '';
                audioUpload.value = '';
                audioPreviewContainer.classList.add('hidden');
            });

            // --- Form Submission Logic ---
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!currentAudioBlob) {
                    alert('Please record or upload an audio file first.');
                    return;
                }

                const context = document.getElementById('context').value;
                if (!context.trim()) {
                    alert('Please provide some context/instructions.');
                    return;
                }

                // UI State Update
                submitBtn.disabled = true;
                submitText.textContent = 'Processing...';
                submitSpinner.classList.remove('hidden');
                
                emptyState.classList.add('hidden');
                errorState.classList.add('hidden');
                resultState.classList.add('hidden');
                loadingState.classList.remove('hidden');

                const formData = new FormData(form);
                // The filename doesn't matter much to the API, but Laravel validation likes an extension
                let extension = 'webm';
                if(currentAudioBlob.name) {
                    extension = currentAudioBlob.name.split('.').pop();
                }
                formData.append('audio', currentAudioBlob, `recording.${extension}`);

                try {
                    const response = await fetch('/gemini-playground/process', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        }
                    });

                    const data = await response.json();
                    loadingState.classList.add('hidden');

                    if (response.ok && data.success) {
                        resultState.classList.remove('hidden');
                        document.getElementById('resText').innerHTML = data.html;
                        document.getElementById('resModel').textContent = data.model;
                        document.getElementById('resTime').textContent = data.time;
                        
                        let tokensText = 'N/A';
                        if (data.tokens) {
                            tokensText = `${data.tokens.totalTokenCount} (In: ${data.tokens.promptTokenCount}, Out: ${data.tokens.candidatesTokenCount})`;
                        }
                        document.getElementById('resTokens').textContent = tokensText;
                        
                        document.getElementById('resRaw').textContent = JSON.stringify(data.raw, null, 2);
                    } else {
                        errorState.classList.remove('hidden');
                        errorMessage.textContent = data.error || 'An unknown error occurred.';
                    }
                } catch (error) {
                    loadingState.classList.add('hidden');
                    errorState.classList.remove('hidden');
                    errorMessage.textContent = 'Network or server error: ' + error.message;
                } finally {
                    submitBtn.disabled = false;
                    submitText.textContent = 'Test with Gemini';
                    submitSpinner.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
