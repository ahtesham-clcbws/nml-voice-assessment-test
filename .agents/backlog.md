# Implementation Plan: Gemini Audio Testing Playground

## 1. Objective
Build a single-page Laravel playground to test Google Gemini's audio understanding and evaluation capabilities. This will serve as an experimental interface to send context and audio (recorded or uploaded) to Gemini and view the full raw response.

## 2. Architecture & Components

### 2.1 Backend (Laravel)
- **Controller:** `GeminiPlaygroundController`
  - `index()`: Render the playground view.
  - `process(Request $request)`: Handle the API request to Gemini.
- **Service:** `GeminiService` (or similar, utilizing `google-genai` or direct HTTP client)
  - Method to send text context + audio file to the Gemini API.
  - Handle exceptions and timeouts gracefully.
- **Routes:** 
  - `GET /gemini-playground`
  - `POST /gemini-playground/process`

### 2.2 Frontend (Blade + Vanilla JS)
- **View:** `resources/views/gemini-playground/index.blade.php`
- **Layout:** Two-column responsive layout (Left: Input, Right: Output).
- **Components:**
  - **Context Input:** Large `<textarea>` for prompt instructions.
  - **Model Selection:** `<select>` for models (e.g., `gemini-1.5-pro`, `gemini-1.5-flash`).
  - **Audio Input:**
    - MediaRecorder API integration for live browser recording.
    - File input `<input type="file" accept="audio/*">` for uploads.
    - Audio playback element `<audio controls>` to review the input.
  - **Submit Action:** "Test with Gemini" button with a loading spinner.
  - **Results Display:**
    - Clean text display for the main response.
    - Expandable `<details>` block for raw JSON API response.
    - Metrics section (Processing time, Token usage, Errors).

## 3. Implementation Steps

1. **Setup & Configuration**
   - Add `GEMINI_API_KEY` to `.env` and `config/services.php`.
   - Ensure a suitable HTTP client or Gemini SDK is installed (e.g., `google-gemini-php/client` or simply use Laravel's `Http` facade).

2. **Routing & Controller**
   - Define web routes in `routes/web.php`.
   - Create `GeminiPlaygroundController`.

3. **Frontend UI (Blade View)**
   - Scaffold the responsive two-column grid layout (using existing TailwindCSS or vanilla CSS).
   - Implement the HTML forms (textarea, file upload, select).
   - Add Vanilla JS for the MediaRecorder API to record, stop, and attach audio blobs to the form.
   - Use AJAX (fetch API or Axios) to submit the form asynchronously to avoid page reloads, allowing repeated testing.

4. **Backend Processing**
   - Validate incoming request (audio file presence/size, context string, model selection).
   - Convert audio to base64 or upload to Gemini File API if required by the model (base64 is usually sufficient for short clips).
   - Construct the payload according to official Gemini API docs.
   - Execute the request and measure processing time (`microtime(true)`).
   - Return structured JSON (text response, raw response, tokens, time, errors) back to the frontend.

5. **Response Handling**
   - Update the UI with the returned JSON data.
   - Format the raw response as readable JSON.

## 4. Security & Restrictions
- All API keys remain server-side.
- Validate audio file type (`mimes:audio/mpeg,wav,webm,ogg` etc.) and enforce a reasonable max size (e.g., 10-20MB).
- Audio processing happens in memory or temporary files that are unlinked immediately after sending to Gemini. No permanent storage of audio files.
- Scope strictly limited to playground (no DB storage of prompts, results, or users required).

## 5. Explicit Sign-off
Awaiting explicit sign-off to proceed with Step 1 (Setup & Configuration).
