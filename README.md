# Smart Grade AI for Moodle

**Smart Grade AI** (`local_smartgradeai`) is a powerful Moodle plugin that integrates advanced AI agents (Gemini, GPT-4, Claude 3.5, etc.) into your grading workflow. It features a "Human-in-the-Loop" architecture, allowing AI to draft grades and feedback based on your rubrics, which teachers can then review, approve, or modify.

## 🚀 Key Features

*   **Multi-Model Support**: Use state-of-the-art models like Gemini 3.0 Pro, GPT-4o, Claude 3.5 Sonnet, DeepSeek V3, and Llama 4.
*   **Rubric Grading**: The AI understands your assignment rubrics and selects the appropriate levels and scores.
*   **Human-in-the-Loop Review**: Enable "Review Mode" to have AI grades save as drafts. Teachers can approve or reject them from a dedicated dashboard.
*   **Student Feedback Button**: rigorous students can request preliminary AI feedback on their submissions before the final deadline to improve their work.
*   **n8n Integration**: Built to work seamlessly with n8n workflows for flexible, low-code grading logic.
*   **Privacy Focused**: Configurable via Moodle's Privacy API.

## 📦 Installation

1.  Clone or download this repository.
2.  Place the folder in your Moodle `local` directory: `your-moodle-site/local/smartgradeai`.
3.  Log in to your Moodle site as an administrator and go to **Site Administration > Notifications** to trigger the database installation.

## ⚙️ Configuration

### 1. Plugin Settings
Go to **Site Administration > Plugins > Local plugins > Smart Grade AI**.

*   **n8n Webhook URL**: The endpoint of your n8n workflow that handles the grading logic.
*   **n8n Token**: A security token to validate requests sent to n8n.
*   **Enable Review Mode**: Global setting to allow the "Review Mode" workflow.
*   **Available AI Models**: List the models you want to make available for teachers to select in their assignment settings.

### 2. Web Service Setup
To allow n8n to send grades back to Moodle, you must enable Web Services:

1.  Go to **Site Administration > Server > Web services > External services**.
2.  Create a new custom service (e.g., "AI Grading Service") and enable it.
3.  Add the following functions to this service:
    *   `local_smartgradeai_save_rubric_grade`: Allows filling rubrics.
    *   `local_smartgradeai_process_review`: Allows approving/rejecting reviews (if using external tools).
    *   `core_course_get_contents`: (Optional) If you need course context.
4.  Create a token for a dedicated "AI Bot" user linked to this service.

## 📖 Usage

### For Teachers

1.  **Activate AI Grading**: 
    *   Go to your Assignment.
    *   Click on the **Actions Menu** (gear icon or "More" menu).
    *   Select **AI Grader Settings**.
    *   Choose your preferred **AI Model** and **Complexity** level.
    *   (Optional) Enable **Student Feedback Button** to let students self-check.

2.  **Reviewing Grades**:
    *   If **Review Mode** is enabled, AI grades will appear in the **Pending AI Reviews** dashboard (accessible via the Assignment settings menu).
    *   Review the rubric selections and comments.
    *   Click **Approve** to push the Grade to the official Gradebook, or **Reject** to discard it.

### For Students
If enabled by the teacher, a **Check AI Feedback** button will appear on the submission status page. Clicking this triggers a request to the AI, which provides formative feedback based on the assignment rubric without affecting the final grade.

## 🔗 n8n Workflow Integration

This plugin relies on an external workflow engine (like n8n) to process the submission text and query the LLM.

**Webhook Payload (sent from Moodle to n8n):**
```json
{
  "assignmentid": 123,
  "submissionid": 456,
  "userid": 789,
  "courseid": 10,
  "contextid": 50,
  "rubric": { ...rubric definition... },
  "submission_text": "Student's essay content...",
  "ai_agent": "gpt-4o",
  "token": "your-security-token"
}
```

**Callback API (n8n to Moodle):**
Use the `local_smartgradeai_save_rubric_grade` function to save the result.

**POST** `https://your-moodle.com/webservice/rest/server.php`

```json
{
  "wstoken": "YOUR_WEBSERVICE_TOKEN",
  "wsfunction": "local_smartgradeai_save_rubric_grade",
  "moodlewsrestformat": "json",
  "assignmentid": 123,
  "userid": 789,
  "rubric_data": [
    {
       "criterionid": 11,
       "levelid": 33,
       "remark": "Excellent argument structure."
    }
  ]
}
```

## 🛠 Troubleshooting

*   **Button not appearing?** Ensure you are in an Assignment context and have the `mod/assign:grade` capability (for teachers).
*   **Grading not saving?** Check `local_smartgradeai_jobs` table for status or errors. Ensure your Web Service token has the correct capabilities.

## License
GPL v3 or later.