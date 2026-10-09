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
        .small-spinner {
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #3B82F6;
            width: 1rem;
            height: 1rem;
            animation: spin 1s ease-in-out infinite;
        }
        .font-urdu-arabic {
            font-family: 'Amiri', 'Noto Naskh Arabic', serif;
            line-height: 1.8;
        }
        .font-english {
            font-family: 'Merriweather', serif;
        }
    </style>
    <link href="/fonts/fonts.css" rel="stylesheet">
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
            <p class="text-gray-600 mt-2">Select a predefined test, upload or record an audio file, and evaluate using Gemini.</p>
        </header>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <!-- Left Column: Input Form -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 h-fit">
                
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Predefined Test</label>
                            <select id="testSelect" name="test_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                                <option value="">Select a test...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gemini Model</label>
                            @if(isset($modelError) && $modelError)
                                <div class="text-sm text-red-600 mt-1 font-medium bg-red-50 p-2 rounded border border-red-200">
                                    {{ $modelError }}
                                </div>
                            @else
                                <select id="modelSelect" name="model" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                                    @foreach($models as $model)
                                        <option value="{{ $model['name'] }}">{{ $model['displayName'] }} ({{ $model['name'] }})</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Leave as default for lowest-cost suitable model.</p>
                            @endif
                        </div>
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
                            <div id="infoText" class="text-lg bg-white p-3 rounded border border-blue-200 text-gray-800 whitespace-pre-wrap leading-relaxed font-english" dir="auto"></div>
                        </div>
                    </div>

                    <!-- Audio Input -->
                    <div class="space-y-4 pt-4 border-t border-gray-200">
                        <label class="block text-sm font-bold text-gray-700">Audio Input</label>
                        <p class="text-xs text-gray-500">Select an audio file or record directly.</p>
                        
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-md">
                            <div class="flex flex-col sm:flex-row gap-3 items-center">
                                <div class="w-full flex flex-col sm:flex-row items-center gap-2">
                                    <input type="file" id="audioUpload" accept="audio/*" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                                    
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button" id="recordBtn" class="px-3 py-1.5 bg-red-100 text-red-700 rounded-md text-xs font-semibold hover:bg-red-200 transition">Record</button>
                                        <button type="button" id="stopBtn" class="hidden px-3 py-1.5 bg-gray-800 text-white rounded-md text-xs font-semibold hover:bg-gray-900 transition">Stop</button>
                                        <audio id="audioPlayback" controls class="hidden h-8 w-40 sm:w-64"></audio>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="submit" id="submitBtn" disabled class="flex-1 flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                <span id="submitText">Evaluate with Gemini</span>
                                <div id="submitSpinner" class="hidden spinner ml-3"></div>
                            </button>
                            <button type="button" id="exportBtn" class="hidden flex-1 flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                <svg class="w-5 h-5 mr-2 -ml-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Export Results (JSON)
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Column: Results Display -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex flex-col h-full min-h-150">
                <h2 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-4">Evaluation Result</h2>
                
                <div id="emptyState" class="flex-1 flex items-center justify-center text-gray-400">
                    Select a test, upload or record student audio, and submit to see results.
                </div>

                <div id="resultsContainer" class="flex-1 space-y-6 overflow-y-auto hidden">
                    <!-- Dynamic results will be injected here -->
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
            
            // DOM Elements
            const languageSelect = document.getElementById('languageSelect');
            const skillSelect = document.getElementById('skillSelect');
            const testSelect = document.getElementById('testSelect');
            
            const testInfo = document.getElementById('testInfo');
            const infoTitle = document.getElementById('infoTitle');
            const infoDifficulty = document.getElementById('infoDifficulty');
            const infoInstruction = document.getElementById('infoInstruction');
            const infoText = document.getElementById('infoText');

            const submitBtn = document.getElementById('submitBtn');
            const exportBtn = document.getElementById('exportBtn');
            let allResults = [];
            
            // Store recorded blob and its mime type temporarily
            let recordedBlob = null;
            let recordedMime = 'audio/webm';

            // HTML Escape utility to prevent XSS
            function escapeHtml(unsafe) {
                return (unsafe || '').toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

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

            function checkSubmitState() {
                const hasTest = !!testSelect.value;
                const fileInput = document.getElementById('audioUpload');
                const hasAudio = fileInput.files.length > 0 || recordedBlob;

                if(hasTest && hasAudio) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }

            function updateTestInfo() {
                const testId = testSelect.value;
                if (!testId) {
                    testInfo.classList.add('hidden');
                    checkSubmitState();
                    return;
                }

                const test = allTests.find(t => t.id === testId);
                infoTitle.textContent = test.title;
                infoDifficulty.textContent = test.difficulty;
                infoInstruction.textContent = test.instruction;
                infoText.textContent = test.display_text;
                
                if(test.language === 'Urdu' || test.language === 'Arabic/Qaida') {
                    infoText.setAttribute('dir', 'rtl');
                    infoText.classList.add('text-right', 'text-4xl', 'font-urdu-arabic');
                    infoText.classList.remove('text-lg', 'font-english');
                } else {
                    infoText.setAttribute('dir', 'ltr');
                    infoText.classList.remove('text-right', 'text-4xl', 'font-urdu-arabic');
                    infoText.classList.add('text-lg', 'font-english');
                }

                testInfo.classList.remove('hidden');
                checkSubmitState();
            }

            languageSelect.addEventListener('change', updateSkills);
            skillSelect.addEventListener('change', updateTests);
            testSelect.addEventListener('change', updateTestInfo);
            
            // --- Audio Recording Logic ---
            const fileInput = document.getElementById('audioUpload');
            const recordBtn = document.getElementById('recordBtn');
            const stopBtn = document.getElementById('stopBtn');
            const audioPlayback = document.getElementById('audioPlayback');
            
            let mediaRecorder;
            let audioChunks = [];

            fileInput.addEventListener('change', () => {
                // Clear recording if a file is uploaded
                if (fileInput.files.length > 0) {
                    recordedBlob = null;
                    audioPlayback.classList.remove('hidden');
                    audioPlayback.src = URL.createObjectURL(fileInput.files[0]);
                    recordBtn.classList.remove('hidden');
                    recordBtn.textContent = 'Record';
                }
                checkSubmitState();
            });

            recordBtn.addEventListener('click', async () => {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);
                    audioChunks = [];

                    mediaRecorder.ondataavailable = event => {
                        if (event.data.size > 0) {
                            audioChunks.push(event.data);
                        }
                    };

                    mediaRecorder.onstop = () => {
                        recordedMime = mediaRecorder.mimeType || 'audio/webm';
                        recordedBlob = new Blob(audioChunks, { type: recordedMime });
                        
                        const audioUrl = URL.createObjectURL(recordedBlob);
                        audioPlayback.src = audioUrl;
                        audioPlayback.classList.remove('hidden');
                        
                        // Clear file input since we have a recording
                        fileInput.value = '';
                        
                        recordBtn.classList.remove('hidden');
                        recordBtn.textContent = 'Re-record';
                        stopBtn.classList.add('hidden');
                        checkSubmitState();
                    };

                    mediaRecorder.start();
                    recordBtn.classList.add('hidden');
                    stopBtn.classList.remove('hidden');
                    audioPlayback.classList.add('hidden');
                    
                } catch (err) {
                    alert('Microphone access is required to record audio.');
                }
            });

            stopBtn.addEventListener('click', () => {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                    mediaRecorder.stream.getTracks().forEach(track => track.stop());
                }
            });

            // --- Form Submission Logic ---
            const form = document.getElementById('testForm');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            const emptyState = document.getElementById('emptyState');
            const resultsContainer = document.getElementById('resultsContainer');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const testId = testSelect.value;
                allResults = [];
                exportBtn.classList.add('hidden');
                
                let audioFileToProcess = null;
                const fileInput = document.getElementById('audioUpload');
                
                if(fileInput.files.length > 0) {
                    audioFileToProcess = fileInput.files[0];
                } else if (recordedBlob) {
                    // For recorded blobs, append them as files with proper extension
                    let ext = 'webm';
                    if (recordedMime.includes('mp4')) ext = 'mp4';
                    else if (recordedMime.includes('ogg')) ext = 'ogg';
                    
                    audioFileToProcess = new File([recordedBlob], `recording.${ext}`, { type: recordedMime });
                }

                if (!audioFileToProcess || !testId) return;

                // UI Loading Setup
                submitBtn.disabled = true;
                submitText.textContent = 'Evaluating...';
                submitSpinner.classList.remove('hidden');
                
                emptyState.classList.add('hidden');
                resultsContainer.classList.remove('hidden');
                resultsContainer.innerHTML = ''; // Clear previous results

                // Create placeholder card
                const card = document.createElement('div');
                card.className = "border border-gray-200 rounded-md bg-gray-50 overflow-hidden shadow-sm";
                card.innerHTML = `
                    <div class="bg-gray-100 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center text-xs text-blue-600 font-medium" id="status">
                            <div class="small-spinner mr-2"></div> Processing...
                        </div>
                    </div>
                    <div class="p-4" id="content">
                        <div class="animate-pulse flex space-x-4">
                            <div class="flex-1 space-y-4 py-1">
                                <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                <div class="space-y-2">
                                    <div class="h-2 bg-gray-300 rounded"></div>
                                    <div class="h-2 bg-gray-300 rounded w-5/6"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                resultsContainer.appendChild(card);
                const statusElement = card.querySelector('#status');
                const contentElement = card.querySelector('#content');

                const formData = new FormData();
                formData.append('test_id', testId);
                formData.append('audio', audioFileToProcess);
                formData.append('model', document.getElementById('modelSelect').value);

                try {
                    const response = await fetch('/gemini-tests/process', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        }
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        statusElement.innerHTML = `<span class="text-green-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Done (${escapeHtml(data.time)})</span>`;
                        contentElement.innerHTML = `
                            <div class="prose prose-sm max-w-none text-gray-800 bg-white p-3 rounded border border-gray-200">
                                ${data.html}
                            </div>
                        `;

                        allResults.push({
                            test_id: testId,
                            model: data.model,
                            success: true,
                            time: data.time,
                            raw_response: data.raw,
                            evaluation_html: data.html
                        });
                    } else {
                        statusElement.innerHTML = `<span class="text-red-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Error</span>`;
                        contentElement.innerHTML = `
                            <div class="bg-red-50 text-red-700 p-3 rounded border border-red-200 text-sm">
                                ${escapeHtml(data.error || 'Evaluation failed.')}
                            </div>
                        `;

                        allResults.push({
                            test_id: testId,
                            success: false,
                            error: data.error || 'Evaluation failed.'
                        });
                    }
                } catch (error) {
                    statusElement.innerHTML = `<span class="text-red-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Error</span>`;
                    contentElement.innerHTML = `
                        <div class="bg-red-50 text-red-700 p-3 rounded border border-red-200 text-sm">
                            Network or server error: ${escapeHtml(error.message)}
                        </div>
                    `;

                    allResults.push({
                        test_id: testId,
                        success: false,
                        error: error.message
                    });
                }

                submitBtn.disabled = false;
                submitText.textContent = 'Evaluate with Gemini';
                submitSpinner.classList.add('hidden');
                
                if (allResults.length > 0) {
                    exportBtn.classList.remove('hidden');
                }
            });

            // Handle export
            exportBtn.addEventListener('click', () => {
                const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(allResults, null, 2));
                const downloadAnchorNode = document.createElement('a');
                downloadAnchorNode.setAttribute("href", dataStr);
                downloadAnchorNode.setAttribute("download", "voice_assessment_results.json");
                document.body.appendChild(downloadAnchorNode);
                downloadAnchorNode.click();
                downloadAnchorNode.remove();
            });
        });
    </script>
</body>
</html>
