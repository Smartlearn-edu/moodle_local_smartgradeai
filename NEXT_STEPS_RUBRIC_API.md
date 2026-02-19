# Status Report: Rubric Grading API

**Date**: 2026-01-31

## Completed Tasks
- [x] **Created API Class**: `classes/external/save_rubric_grade.php` is implemented. It accepts a simplified list of criterion ID/level IDs and converts them to Moodle's internal `advancedgrading` format.
- [x] **Registered Function**: Added `local_autogradehelper_save_rubric_grade` to `db/services.php`.
- [x] **Version Update**: Bumped `version.php` to `2026013100` to ensure Moodle detects the changes.

## To-Do (Next Session)
1.  **Update Moodle**:
    *   Go to **Site Administration > Notifications** to run the database upgrade for this plugin.
    *   Go to **Site Administration > Server > Web services > External services**.
    *   Find the service user you are using for n8n.
    *   **Add Function**: Search for and add `local_autogradehelper_save_rubric_grade` to their allowed functions.

2.  **Test with n8n**:
    *   Use the HTTP Request node configuration below to test sending a grade.

## n8n HTTP Request Configuration

*   **Method**: `POST`
*   **URL**: `https://YOUR_MOODLE_SITE/webservice/rest/server.php` (Replace with your actual URL)
*   **Body Content Type**: `JSON`

**JSON Payload**:
```json
{
  "wstoken": "YOUR_TOKEN_HERE",
  "wsfunction": "local_autogradehelper_save_rubric_grade",
  "moodlewsrestformat": "json",
  "assignmentid": 34,
  "userid": 5,
  "rubric_data": [
    {
      "criterionid": 11,
      "levelid": 33,
      "remark": "Feedback for criterion 1"
    },
    {
      "criterionid": 12,
      "levelid": 37,
      "remark": "Feedback for criterion 2"
    }
  ]
}
```
