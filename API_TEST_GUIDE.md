# Testing Moodle Rubric API with Postman

The error `Invalid parameter value detected` occurs because Moodle does not automatically parse JSON strings passed in the URL (GET request) for complex structures like `rubric_data`. It expects PHP-style array syntax in the URL if you are not doing a JSON POST.

## Option 1: JSON Body (Recommended)

In Postman:
1. **Method**: Set to `POST`.
2. **URL**: `https://smartlearn.education/webservice/rest/server.php?wstoken=2550a2cd0b216f02a5e2e50192698a90&wsfunction=local_autogradehelper_save_rubric_grade&moodlewsrestformat=json`
3. **Headers**: Add `Content-Type: application/json`.
4. **Body**: Select **raw** -> **JSON**.
5. **Paste**:
   ```json
   {
       "assignmentid": 13,
       "userid": 15,
       "rubric_data": [
           {
               "criterionid": 11,
               "levelid": 33,
               "remark": "This is feedback for the first row."
           },
           {
               "criterionid": 12,
               "levelid": 37,
               "remark": "This is feedback for the second row."
           }
       ]
   }
   ```

## Option 2: GET Request (URL Parameters)

If you strictly want to use the URL line (GET), you must use bracket syntax for arrays. You **cannot** put a JSON string like `[...]` as a value.

**Copy this exact URL:**

`https://smartlearn.education/webservice/rest/server.php?wstoken=2550a2cd0b216f02a5e2e50192698a90&wsfunction=local_autogradehelper_save_rubric_grade&moodlewsrestformat=json&assignmentid=13&userid=15&rubric_data[0][criterionid]=11&rubric_data[0][levelid]=33&rubric_data[0][remark]=First_feedback&rubric_data[1][criterionid]=12&rubric_data[1][levelid]=37&rubric_data[1][remark]=Second_feedback`

### Why this looks messy
GET parameters mimic PHP form arrays:
- `rubric_data[0][criterionid]=11`
- `rubric_data[0][levelid]=33`
- ...

This is why **Option 1 (POST with JSON)** is much better for lists of data.

## Option 3: cURL Command

```bash
curl --location 'https://smartlearn.education/webservice/rest/server.php?wstoken=2550a2cd0b216f02a5e2e50192698a90&wsfunction=local_autogradehelper_save_rubric_grade&moodlewsrestformat=json' \
--header 'Content-Type: application/json' \
--data '{
    "assignmentid": 13,
    "userid": 15,
    "rubric_data": [
        {
            "criterionid": 11,
            "levelid": 33,
            "remark": "This is feedback for the first row."
        },
        {
            "criterionid": 12,
            "levelid": 37,
            "remark": "This is feedback for the second row."
        }
    ]
}'
```
