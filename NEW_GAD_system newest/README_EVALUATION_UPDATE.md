# Recent Error Fixes (April 25, 2023)

## Fixed Issues
1. **Constant Redefinition Warnings**
   - Problem: `Warning: Constant DB_HOST already defined in includes/db_connection.php`
   - Fixed by adding conditional checks before including db_connection.php to prevent duplicate constant definitions
   - Added code to check if constants are already defined using if (!defined('DB_HOST'))
   - Applied fix to narrative_handler.php, image_upload_handler.php, and remove_image.php

2. **mysqli_stmt::rowCount() Error**
   - Problem: `Call to undefined method mysqli_stmt::rowCount() in narrative_handler.php`
   - Fixed by replacing PDO's rowCount() method with mysqli's num_rows for checking column existence
   - Fixed `affected_rows` property usage for update operations

3. **mysqli_stmt::fetch() Error**
   - Problem: `mysqli_stmt::fetch() expects exactly 0 arguments, 1 given in narrative_handler.php line 394`
   - Fixed by ensuring consistent use of mysqli methods in narrative_handler.php
   - Added proper mysqli style parameter binding and result handling

4. **Database Connection Method Consistency**
   - Fixed mixed use of PDO and mysqli methods in the same files
   - Made narrative_handler.php consistently use mysqli
   - Made image_upload_handler.php and remove_image.php consistently use PDO
   - Added conditional checks to prevent duplicate inclusion of database connection files

5. **Image Upload Issues**
   - Increased image file size limit from 5MB to 50MB
   - Added MAX_FILE_SIZE hidden field to the form
   - Added detailed error messages for various upload error cases
   - Improved error display to show specific file errors

6. **Error Handling Improvements**
   - Enhanced error message parsing and display for both upload and form submission
   - Better handling of PHP errors in AJAX responses
   - More detailed error messages with contextual information

7. **Preserving Existing Images During Failed Uploads**
   - Problem: When uploading new images failed, existing images were not displayed and appeared to be lost
   - Fixed by modifying image_upload_handler.php to preserve existing images when uploads fail
   - Enhanced error handling to return existing images in the response even when uploads fail
   - Updated JavaScript to display existing images after upload failures
   - Added support for partial success scenarios where some images upload correctly and others fail

## Testing Steps
1. Upload a large image (10MB+) to verify file size limits work
2. Edit an existing narrative with evaluation data to verify it loads correctly
3. Check that the Number of Beneficiaries ratings are properly displayed in edit mode
4. Verify image previews work when adding new images to an existing narrative
5. Test removing images from narratives

## Original Error Messages
```
data_entry.php:2657 AJAX Error: parsererror - SyntaxError: Unexpected token '<', " <br />
<fo"... is not valid JSON
```

```
Fatal error: Uncaught ArgumentCountError: mysqli_stmt::fetch() expects exactly 0 arguments, 1 given in narrative_handler.php line 394
```

The error was caused by mixing mysqli and PDO database access methods. The system is now consistent in using the correct methods for each database connection type.

## Additional Improvements
- More robust file type checking
- File size validation with detailed error messages
- Image path handling consistency

data_entry.php:2657 AJAX Error: parsererror - SyntaxError: Unexpected token '<', " <br />
<fo"... is not valid JSON
error @ data_entry.php:2657
c @ jquery-3.6.0.min.js:2
fireWith @ jquery-3.6.0.min.js:2
l @ jquery-3.6.0.min.js:2
(anonymous) @ jquery-3.6.0.min.js:2
XMLHttpRequest.send
send @ jquery-3.6.0.min.js:2
ajax @ jquery-3.6.0.min.js:2
handleFormSubmit @ data_entry.php:2583
(anonymous) @ data_entry.php:2199
data_entry.php:2658 Response Text:  <br />
<font size='1'><table class='xdebug-error xe-uncaught-exception' dir='ltr' border='1' cellspacing='0' cellpadding='1'>
<tr><th align='left' bgcolor='#f57900' colspan="5"><span style='background-color: #cc0000; color: #fce94f; font-size: x-large;'>( ! )</span> Fatal error: Uncaught Error: Call to undefined method mysqli_stmt::rowCount() in C:\wamp64\www\NEW_GAD_system newest\narrative_data_entry\narrative_handler.php on line <i>217</i></th></tr>
<tr><th align='left' bgcolor='#f57900' colspan="5"><span style='background-color: #cc0000; color: #fce94f; font-size: x-large;'>( ! )</span> Error: Call to undefined method mysqli_stmt::rowCount() in C:\wamp64\www\NEW_GAD_system newest\narrative_data_entry\narrative_handler.php on line <i>217</i></th></tr>
<tr><th align='left' bgcolor='#e9b96e' colspan='5'>Call Stack</th></tr>
<tr><th align='center' bgcolor='#eeeeec'>#</th><th align='left' bgcolor='#eeeeec'>Time</th><th align='left' bgcolor='#eeeeec'>Memory</th><th align='left' bgcolor='#eeeeec'>Function</th><th align='left' bgcolor='#eeeeec'>Location</th></tr>
<tr><td bgcolor='#eeeeec' align='center'>1</td><td bgcolor='#eeeeec' align='center'>0.0006</td><td bgcolor='#eeeeec' align='right'>374184</td><td bgcolor='#eeeeec'>{main}(  )</td><td title='C:\wamp64\www\NEW_GAD_system newest\narrative_data_entry\narrative_handler.php' bgcolor='#eeeeec'>...\narrative_handler.php<b>:</b>0</td></tr>
<tr><td bgcolor='#eeeeec' align='center'>2</td><td bgcolor='#eeeeec' align='center'>0.0041</td><td bgcolor='#eeeeec' align='right'>470488</td><td bgcolor='#eeeeec'>handleCreate(  )</td><td title='C:\wamp64\www\NEW_GAD_system newest\narrative_data_entry\narrative_handler.php' bgcolor='#eeeeec'>...\narrative_handler.php<b>:</b>39</td></tr>
<tr><td bgcolor='#eeeeec' align='center'>3</td><td bgcolor='#eeeeec' align='center'>0.0041</td><td bgcolor='#eeeeec' align='right'>470520</td><td bgcolor='#eeeeec'>handleFormSubmission(  )</td><td title='C:\wamp64\www\NEW_GAD_system newest\narrative_data_entry\narrative_handler.php' bgcolor='#eeeeec'>...\narrative_handler.php<b>:</b>425</td></tr>
</table></font>

data_entry.php:1753 Uploading images for narrative ID: 4, campus: Lipa
data_entry.php:1761 Adding file to upload: GqVyH2GbwAAhbrG.jpeg (4527312 bytes)
data_entry.php:1787 Image upload response: {success: false, message: 'No images were uploaded successfully', errors: Array(1)}