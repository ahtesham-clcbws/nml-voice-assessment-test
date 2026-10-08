<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Testing Suite</title>
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
            <a href="/gemini-playground" class="text-gray-500 hover:text-gray-700 font-medium">Free-form Playground</a>
            <a href="/gemini-tests" class="text-blue-600 font-medium border-b-2 border-blue-600 pb-4 -mb-4">Audio Testing Suite</a>
        </nav>

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Audio Testing Suite</h1>
            <p class="text-gray-600 mt-2">Select a predefined test, record your voice, and evaluate using Gemini.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column: Input Form -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                
                <form id="testForm" class="space-y-6">
                    @csrf
                    
                    <!-- Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Language</label>
                            <select id="languageSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                                <option value="">Select Language</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Skill</label>
                            <select id="skillSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                                <option value="">Select Skill</option>
                            </select>
                        </div>
                    </div>

                    <!-- Test Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Predefined Test</label>
                        <select id="testSelect" name="test_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                            <option value="">Select a test...</option>
                        </select>
                    </div>

                    <!-- Test Information Display -->
                    <div id="testInfo" class="hidden bg-blue-50 rounded-md p-4 border border-blue-100 space-y-3">
                        <div class="flex justify-between items-start">
                            <h3 id="infoTitle" class="text-md font-bold text-blue-900"></h3>
                            <span id="infoDifficulty" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-200 text-blue-800"></span>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-1">Instructions</h4>
                            <p id="infoInstruction" class="text-sm text-blue-900 whitespace-pre-wrap"></p>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-1">Text to Read</h4>
                            <div id="infoText" class="text-lg bg-white p-3 rounded border border-blue-200 text-gray-800 whitespace-pre-wrap leading-relaxed font-serif" dir="auto"></div>
                        </div>
                    </div>

                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700">Gemini Model</label>
                        <select id="model" name="model" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                            @foreach($models as $model)
                                <option value="{{ $model['name'] }}">{{ $model['displayName'] }} ({{ $model['name'] }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Audio Input -->
                    <div class="space-y-3 pt-2 border-t border-gray-100">
                        <label class="block text-sm font-medium text-gray-700">Audio Input</label>
                        
                        <div class="flex items-center space-x-4">
                            <button type="button" id="recordBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
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
                            <button type="button" id="clearAudioBtn" class="mt-2 text-sm text-red-600 hover:text-red-800">Clear Audio / Re-record</button>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200">
                        <button type="submit" id="submitBtn" disabled class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <span id="submitText">Evaluate with Gemini</span>
                            <div id="submitSpinner" class="hidden spinner ml-3"></div>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Results Display -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex flex-col h-full min-h-[600px]">
                <h2 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-4">Evaluation Results</h2>
                
                <div id="loadingState" class="hidden flex-1 flex flex-col items-center justify-center text-gray-500">
                    <div class="spinner border-blue-500 border-t-transparent w-8 h-8 mb-4"></div>
                    <p>Evaluating audio with Gemini...</p>
                </div>

                <div id="emptyState" class="flex-1 flex items-center justify-center text-gray-400">
                    Select a test and submit audio to see the results.
                </div>

                <div id="errorState" class="hidden bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-medium" id="errorMessage"></p>
                        </div>
                    </div>
                </div>

                <div id="resultState" class="hidden flex-1 flex flex-col">
                    <div class="flex space-x-4 mb-4 text-xs flex-wrap gap-y-2">
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

                    <div class="flex-1 bg-gray-50 p-4 rounded-md overflow-y-auto border border-gray-200 prose prose-sm max-w-none text-gray-800" id="resText"></div>

                    <details class="mt-4 border-t pt-4">
                        <summary class="cursor-pointer text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none">View Raw Gemini Response</summary>
                        <pre id="resRaw" class="mt-2 text-xs bg-gray-800 text-green-400 p-4 rounded-md overflow-x-auto"></pre>
                    </details>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass tests JSON to Javascript -->
    <script>
        const allTests = {!! json_encode($tests) !!};
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // State
            let currentAudioBlob = null;
            let mediaRecorder;
            let audioChunks = [];
            
            // DOM Elements
            const languageSelect = document.getElementById('languageSelect');
            const skillSelect = document.getElementById('skillSelect');
            const testSelect = document.getElementById('testSelect');
            
            const testInfo = document.getElementById('testInfo');
            const infoTitle = document.getElementById('infoTitle');
            const infoDifficulty = document.getElementById('infoDifficulty');
            const infoInstruction = document.getElementById('infoInstruction');
            const infoText = document.getElementById('infoText');

            const recordBtn = document.getElementById('recordBtn');
            const submitBtn = document.getElementById('submitBtn');

            // --- Populate Dropdowns ---
            const languages = [...new Set(allTests.map(t => t.language))];
            languages.forEach(l => {
                const opt = document.createElement('option');
                opt.value = l;
                opt.textContent = l;
                languageSelect.appendChild(opt);
            });

            function updateSkills() {
                skillSelect.innerHTML = '<option value="">Select Skill</option>';
                testSelect.innerHTML = '<option value="">Select a test...</option>';
                testInfo.classList.add('hidden');
                
                const lang = languageSelect.value;
                if (!lang) return;

                const skills = [...new Set(allTests.filter(t => t.language === lang).map(t => t.skill))];
                skills.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s;
                    opt.textContent = s;
                    skillSelect.appendChild(opt);
                });
            }

            function updateTests() {
                testSelect.innerHTML = '<option value="">Select a test...</option>';
                testInfo.classList.add('hidden');

                const lang = languageSelect.value;
                const skill = skillSelect.value;
                if (!lang || !skill) return;

                const filtered = allTests.filter(t => t.language === lang && t.skill === skill);
                filtered.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.title;
                    testSelect.appendChild(opt);
                });
            }

            function updateTestInfo() {
                const testId = testSelect.value;
                if (!testId) {
                    testInfo.classList.add('hidden');
                    recordBtn.disabled = true;
                    submitBtn.disabled = true;
                    return;
                }

                const test = allTests.find(t => t.id === testId);
                infoTitle.textContent = test.title;
                infoDifficulty.textContent = test.difficulty;
                infoInstruction.textContent = test.instruction;
                infoText.textContent = test.display_text;
                
                // Adjust text direction for Arabic/Urdu
                if(test.language === 'Urdu' || test.language === 'Arabic/Qaida') {
                    infoText.setAttribute('dir', 'rtl');
                    infoText.classList.add('text-right');
                } else {
                    infoText.setAttribute('dir', 'ltr');
                    infoText.classList.remove('text-right');
                }

                testInfo.classList.remove('hidden');
                recordBtn.disabled = false;
                checkSubmitState();
            }

            languageSelect.addEventListener('change', updateSkills);
            skillSelect.addEventListener('change', updateTests);
            testSelect.addEventListener('change', updateTestInfo);


            // --- Audio Logic ---
            const stopBtn = document.getElementById('stopBtn');
            const recordingStatus = document.getElementById('recordingStatus');
            const audioUpload = document.getElementById('audioUpload');
            const audioPreviewContainer = document.getElementById('audioPreviewContainer');
            const audioPlayback = document.getElementById('audioPlayback');
            const clearAudioBtn = document.getElementById('clearAudioBtn');

            function checkSubmitState() {
                if(testSelect.value && currentAudioBlob) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }

            recordBtn.addEventListener('click', async () => {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);
                    
                    mediaRecorder.ondataavailable = (e) => {
                        if (e.data.size > 0) audioChunks.push(e.data);
                    };

                    mediaRecorder.onstop = () => {
                        currentAudioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                        audioPlayback.src = URL.createObjectURL(currentAudioBlob);
                        audioPreviewContainer.classList.remove('hidden');
                        audioUpload.value = ''; 
                        stream.getTracks().forEach(track => track.stop());
                        checkSubmitState();
                    };

                    audioChunks = [];
                    mediaRecorder.start();
                    
                    recordBtn.classList.add('hidden');
                    stopBtn.classList.remove('hidden');
                    recordingStatus.classList.remove('hidden');
                } catch (err) {
                    alert('Microphone error: ' + err.message);
                }
            });

            stopBtn.addEventListener('click', () => {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
                stopBtn.classList.add('hidden');
                recordBtn.classList.remove('hidden');
                recordingStatus.classList.add('hidden');
            });

            audioUpload.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    currentAudioBlob = file;
                    audioPlayback.src = URL.createObjectURL(file);
                    audioPreviewContainer.classList.remove('hidden');
                    checkSubmitState();
                }
            });

            clearAudioBtn.addEventListener('click', () => {
                currentAudioBlob = null;
                audioPlayback.src = '';
                audioUpload.value = '';
                audioPreviewContainer.classList.add('hidden');
                checkSubmitState();
            });


            // --- Form Submission Logic ---
            const form = document.getElementById('testForm');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            const emptyState = document.getElementById('emptyState');
            const loadingState = document.getElementById('loadingState');
            const errorState = document.getElementById('errorState');
            const errorMessage = document.getElementById('errorMessage');
            const resultState = document.getElementById('resultState');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!currentAudioBlob || !testSelect.value) return;

                // UI Loading
                submitBtn.disabled = true;
                submitText.textContent = 'Evaluating...';
                submitSpinner.classList.remove('hidden');
                emptyState.classList.add('hidden');
                errorState.classList.add('hidden');
                resultState.classList.add('hidden');
                loadingState.classList.remove('hidden');

                const formData = new FormData(form);
                let extension = currentAudioBlob.name ? currentAudioBlob.name.split('.').pop() : 'webm';
                formData.append('audio', currentAudioBlob, `recording.${extension}`);

                try {
                    const response = await fetch('/gemini-tests/process', {
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
                        
                        // Parse markdown-like tables or bold tags manually if needed, 
                        // but since Tailwind prose handles basic markdown if we include a markdown parser,
                        // for now we just dump text. (A proper MD parser is ideal, using simple regex for bold)
                        let parsedText = data.text
                                            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                            .replace(/\*(.*?)\*/g, '<em>$1</em>')
                                            .replace(/\n/g, '<br>');
                        
                        document.getElementById('resText').innerHTML = parsedText;
                        document.getElementById('resModel').textContent = data.model;
                        document.getElementById('resTime').textContent = data.time;
                        
                        let tokensText = 'N/A';
                        if (data.tokens) tokensText = `${data.tokens.totalTokenCount}`;
                        document.getElementById('resTokens').textContent = tokensText;
                        
                        document.getElementById('resRaw').textContent = JSON.stringify(data.raw, null, 2);
                    } else {
                        errorState.classList.remove('hidden');
                        errorMessage.textContent = data.error || 'Evaluation failed.';
                    }
                } catch (error) {
                    loadingState.classList.add('hidden');
                    errorState.classList.remove('hidden');
                    errorMessage.textContent = 'Error: ' + error.message;
                } finally {
                    submitBtn.disabled = false;
                    submitText.textContent = 'Evaluate with Gemini';
                    submitSpinner.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
