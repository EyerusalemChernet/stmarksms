# AI Features — St. Mark School ERP

## Overview

The system integrates AI-powered features using **Ollama** running locally with the **TinyLlama** model. All AI features are optional and degrade gracefully — the system works fully without Ollama.

---

## Feature 1: Evidence-Based Report Card Comment Generation

**Location:** Academics → Marks → Enter Marks (⭐ button per student)

**How it works:**
1. Teacher enters Assessment, Mid Exam, and Final Exam scores
2. Clicking ⭐ sends scores to `POST /ai/generate-comment`
3. `AICommentService` detects the student's learning pattern from score distribution
4. A structured prompt is built with evidence and recommended focus area
5. TinyLlama generates a 2-3 sentence professional comment
6. Comment appears in the textarea — teacher reviews and saves

**Pattern Detection (7 patterns):**

| Pattern | Trigger | Evidence Injected |
|---|---|---|
| `strong_coursework_weak_exam` | Coursework ≥70%, Exam <50% | "Good understanding but difficulty under exam conditions" |
| `strong_exam_weak_coursework` | Exam ≥70%, Coursework <50% | "Strong exam performance, inconsistent daily work" |
| `significant_struggle` | Total < 45 | "Would benefit from targeted intervention" |
| `excellence` | Total ≥ 85 | "Mastery demonstrated — enrichment recommended" |
| `significant_drop` | Drop > 15 pts vs previous | "Performance declined — supportive conversation recommended" |
| `significant_improvement` | Gain > 15 pts vs previous | "Remarkable improvement — effort should be celebrated" |
| `consistent` | Default | "Performing at a consistent level" |

**Fallback:** If Ollama is unreachable, a hardcoded comment based on performance level is returned.

**Defense talking point:** *"The system doesn't just generate generic comments — it analyses the relationship between coursework and exam performance to identify specific learning patterns, then instructs the AI to reference that evidence in the comment. This aligns with formative assessment principles."*

---

## Feature 2: Parent Message Summarization

**Location:** Communication → Inbox → Read Message (messages > 200 characters)

**How it works:**
1. Parent or teacher opens a long message
2. "Summarize with AI" button appears automatically for messages > 200 chars
3. Clicking sends the message body to `POST /ai/summarize-message`
4. TinyLlama returns a 1-2 sentence summary
5. Summary slides in below the message body

**Use case:** Busy teachers receiving long parent messages can quickly understand the key point before reading in full.

**Defense talking point:** *"This reduces cognitive load for teachers who receive many parent messages, allowing them to triage and prioritise responses more efficiently."*

---

## Feature 3: Smart Performance Insights Dashboard

**Location:** Academics → Marks → Smart Insights

**How it works (pure PHP, no AI API):**
- Queries `ExamRecord` for current and previous exam averages
- Identifies at-risk students (average < 50% OR dropped > 15%)
- Calculates class health (good/warning/critical)
- Finds subject-level averages and declining trends
- Ranks top performers and most improved students

**No Ollama required** — this is algorithmic analysis, not generative AI.

**Defense talking point:** *"The performance insights dashboard implements evidence-based early intervention logic. By comparing current and previous exam records, it automatically surfaces students who need support before they fall further behind."*

---

## Feature 4: Dropout Early Warning System

**Location:** Academics → Attendance → Early Warning

**How it works (pure PHP, no AI API):**

Risk score calculated from 5 weighted factors:

| Factor | Weight |
|---|---|
| Attendance below 65% | 30 pts |
| Attendance 65–74% (below MoE minimum) | 15 pts |
| Attendance declining > 10 percentage points | 20 pts |
| Academic average below 50% | 25 pts |
| Grades declining > 15 points | 15 pts |
| 5+ consecutive absences | 10 pts |

Risk levels: Critical (50+), Warning (25–49), Low (0–24)

Aligned with **Ethiopian Ministry of Education 75% minimum attendance requirement**.

**Defense talking point:** *"The early warning system operationalises the Ministry of Education's attendance policy into an automated risk score. Rather than waiting for end-of-term reports, administrators can identify at-risk students weekly and intervene early — which is the core purpose of an ERP in an educational context."*

---

## Feature 5: Timetable Conflict Detector

**Location:** Academics → Timetable → Manage → Validate Timetable

**How it works (pure PHP, no AI API):**
- Detects 4 conflict types: class double-booked, teacher double-booked, subject not scheduled, subject repeated same day
- Cross-checks teacher assignments across all timetables in the same year
- Generates specific fix suggestions (names available teachers, suggests free slots)

**Defense talking point:** *"The conflict detector prevents scheduling errors before they affect students. By checking teacher availability across all classes simultaneously, it catches conflicts that would be invisible when editing a single timetable."*

---

## Technical Architecture

```
Browser → Laravel Controller → AICommentService → Guzzle HTTP → Ollama REST API
                                                              ↓
                                                    TinyLlama model
                                                              ↓
                                                    JSON response
                                                              ↓
                                              Fallback if unreachable
```

**No external API keys required.** Ollama runs entirely on the local machine — student data never leaves the school's network.

---

## Setup

```bash
# Install Ollama
# Windows: https://ollama.com/download

# Pull model
ollama pull tinyllama

# Verify
ollama run tinyllama "Hello"
```

Add to `.env`:
```
OLLAMA_MODEL=tinyllama
OLLAMA_URL=http://127.0.0.1:11434
```

---

## Files

| File | Purpose |
|---|---|
| `app/Services/AICommentService.php` | Comment generation + message summarization |
| `app/Services/PerformanceAnalysisService.php` | Smart insights dashboard |
| `app/Services/AttendanceRiskService.php` | Dropout early warning |
| `app/Services/TimetableValidationService.php` | Conflict detection |
| `app/Http/Controllers/AICommentController.php` | API endpoints for AI features |
| `routes/web.php` | `POST /ai/generate-comment`, `POST /ai/summarize-message` |
