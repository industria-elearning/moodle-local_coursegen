<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_datacurso
 * @category    string
 * @copyright   Josue <josue@datacurso.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accept_planning_create_course'] = 'Accept and create course';
$string['actions'] = 'Actions';
$string['addactivityai_arialabel'] = 'AI assistant to create resources/activities';
$string['addactivityai_created_cmid'] = 'Resource/activity created. (cmid: {$a})';
$string['addactivityai_created_named'] = 'Created: {$a->type} "{$a->name}"';
$string['addactivityai_done'] = 'Done! The resource/activity was created.';
$string['addactivityai_error'] = 'An error occurred while creating the resource. Please try again or modify the prompt.';
$string['addactivityai_faildefault'] = 'It was not possible to create the resource.';
$string['addactivityai_label'] = 'Describe what you need';
$string['addactivityai_modaltitle'] = 'Create resource/activity with AI';
$string['addactivityai_next_no'] = 'No, return to course';
$string['addactivityai_next_text'] = 'Do you want to create another resource?';
$string['addactivityai_next_yes'] = 'Yes, create another';
$string['addactivityai_placeholder'] = 'Describe what resource or activity you want to create';
$string['addactivityai_warnings_prefix'] = 'Warnings: {$a}';
$string['addactivityai_welcome'] = 'Hi! Tell me what resource or activity you need and I will create it in your course. 😊';
$string['addactivitywithia'] = 'Add activity or resource with AI';
$string['addcourseai_arialabel'] = 'AI assistant to create courses';
$string['addcourseai_done'] = 'Done! The course was created successfully.';
$string['addcourseai_error'] = 'An error occurred while creating the course. Please try again or modify the prompt.';
$string['addcourseai_faildefault'] = 'It was not possible to create the course.';
$string['addcourseai_label'] = 'Describe the course you want to create';
$string['addcourseai_modaltitle'] = 'Create course with AI';
$string['addcourseai_placeholder'] = 'Describe what kind of course you want to create';
$string['addcourseai_welcome'] = 'Hi! Tell me what course you need and I will help you create it. 😊';
$string['addmodel'] = 'Add Model';
$string['adjust_course_planning'] = 'Adjust course planning';
$string['adjust_planning_description'] = 'Describe any changes you want to make to the course planning.';
$string['adjust_planning_title'] = 'Adjust course planning';
$string['ai_almost_ready'] = 'Almost ready! AI is making final adjustments...';
$string['ai_analyzing_content'] = 'AI is analyzing your content...';
$string['ai_creating_activities'] = 'AI is creating activities and resources...';
$string['ai_creating_course'] = 'AI is Creating Your Course';
$string['ai_finalizing_course'] = 'AI is finalizing course details...';
$string['ai_generating_structure'] = 'AI is generating course structure...';
$string['ai_loading_please_wait'] = 'Please wait while AI creates your personalized course content. This may take a few minutes.';
$string['ai_organizing_materials'] = 'AI is organizing learning materials...';
$string['apitoken'] = 'API Token';
$string['apitoken_desc'] = 'Enter your API token here.';
$string['baseurl'] = 'API Base URL';
$string['baseurl_desc'] = 'Enter the base URL of the DataCurso API with version. e.g. https://api.datacurso.com/api/v3';
$string['cachedef_apitoken'] = 'Cache for API token obtained from DataCurso';
$string['cannotgenerateuniqueshortname'] = 'Cannot generate unique course shortname';
$string['choosemodel'] = 'Choose model';
$string['click_to_view_details'] = 'Click to view details';
$string['confirmdelete'] = 'Are you sure you want to delete this model?';
$string['context_type_field'] = 'Context Type';
$string['context_type_model'] = 'Instructional Model';
$string['context_type_syllabus'] = 'Syllabus';
$string['course_content_field'] = 'Define the COURSE CONTENT.';
$string['course_created_success'] = 'Course created successfully!';
$string['course_created_success_simple'] = '✅ Course created successfully';
$string['course_creating_subtitle'] = 'Generating course content...';
$string['course_creating_title'] = 'Creating the course';
$string['course_description_field'] = 'Define a COURSE DESCRIPTION for the subject.';
$string['course_planning_started'] = 'Course planning session started successfully.';
$string['course_streaming_loading'] = 'Loading...';
$string['course_streaming_planning_subtitle'] = 'Generating course content...';
$string['course_streaming_planning_title'] = 'Planning course creation';
$string['course_structure_field'] = 'Define the COURSE STRUCTURE.';
$string['course_summary_field'] = 'Define the COURSE SUMMARY.';
$string['coursecreated'] = 'Course created successfully';
$string['coursecreatedsuccess'] = 'Course created successfully.';
$string['createwithai'] = 'Create with AI';
$string['creating_course'] = 'Creating Course...';
$string['custom_fields_header'] = 'DataCurso';
$string['custom_formation_select_field'] = 'Choose the most appropriate EDUCATION LEVEL for your subject.';
$string['custom_model_select_field'] = 'Choose the model to use';
$string['custom_program_text_field'] = 'Define the PROGRAM associated with this subject.';
$string['custom_semester_select_field'] = 'Select the SEMESTER in which this subject will be taught.';
$string['custom_text_field_help'] = 'Enter a custom text for this course.';
$string['datacurso:managemodels'] = 'Manage models';
$string['datacurso:view_syllabus'] = 'View syllabus';
$string['delete'] = 'Delete';
$string['deletemodel'] = 'Delete Model';
$string['edit'] = 'Edit';
$string['editmodel'] = 'Edit Model';
$string['enter_message'] = 'Enter your message';
$string['error_api_config'] = 'API configuration is missing. Please check your settings.';
$string['error_api_not_configured'] = 'API configuration is missing. Please check your settings.';
$string['error_api_request_failed'] = 'Failed to send message to AI service. Please try again.';
$string['error_creating_course'] = '❌ Error creating course: {$a}';
$string['error_executing_plan'] = 'Error executing the course plan';
$string['error_file_too_large'] = 'The file size exceeds the maximum allowed (10MB).';
$string['error_generating_resource'] = 'There was a problem generating the requested resource. Please try again later.';
$string['error_http_code'] = 'HTTP error code: {$a}';
$string['error_invalid_api_response'] = 'Invalid response from AI service.';
$string['error_invalid_number'] = 'Invalid number';
$string['error_missing_config'] = 'Missing API configuration. Please check your settings.';
$string['error_no_course_id'] = 'Could not get course ID';
$string['error_no_file_found'] = 'No file was found to upload.';
$string['error_no_session_found'] = 'No planning session found for this course and user.';
$string['error_not_pdf'] = 'The uploaded file must be a PDF.';
$string['error_pdf_generation'] = 'Error generating PDF: {$a}';
$string['error_processing_request'] = 'Error processing your request';
$string['error_saving_session'] = 'Failed to save the planning session. Please try again.';
$string['error_sending_message'] = 'Error sending message';
$string['error_starting_course_planning'] = 'There was an error starting the course planning session. Please try again.';
$string['error_text_too_short'] = 'The text must be at least 3 characters long.';
$string['error_upload_failed'] = 'Failed to upload syllabus to AI service: {$a}';
$string['error_upload_model_failed'] = 'Failed to upload model to AI service: {$a}';
$string['execution_activity_done'] = '✅ Activity completed ({$a->done}/{$a->total}) — {$a->percent}%';
$string['execution_activity_start'] = '🧩 Starting activity #{$a->index} (section {$a->section}): {$a->title}';
$string['execution_error_activity'] = '❌ Error in an activity';
$string['execution_progress'] = '📈 Progress: {$a->done}/{$a->total} ({$a->percent}%)';
$string['formation_option1'] = 'Technical Professional';
$string['formation_option2'] = 'Technological';
$string['formation_option3'] = 'University Professional';
$string['generalsettings'] = 'General Settings';
$string['generated_on'] = 'Generated on';
$string['generatewithai'] = 'Generate with AI';
$string['invalidmodel'] = 'Invalid model';
$string['learning_outcomes_field'] = 'Define the LEARNING OUTCOMES expected for the subject.';
$string['managemodels'] = 'Manage Models';
$string['message_sent_successfully'] = 'Message sent successfully to AI planning session.';
$string['modelcontent'] = 'Model Content';
$string['modelcreated'] = 'Created';
$string['modeldeleted'] = 'Model deleted successfully';
$string['modelmodified'] = 'Modified';
$string['modelname'] = 'Model Name';
$string['modelnameexists'] = 'A model with this name already exists';
$string['modelsaved'] = 'Model saved successfully';
$string['modelsavedanduploaded'] = 'Model saved and uploaded to AI successfully';
$string['modelsaveduploadfailed'] = 'Model saved but failed to upload to AI';
$string['nocoursesavailable'] = 'You have no courses available in this model.';
$string['noimages'] = 'Do not generate images';
$string['nomodels'] = 'No models found';
$string['number_of_modules_field'] = 'How many modules or topics will the course have?';
$string['option1'] = 'Model 1';
$string['option2'] = 'Model 2';
$string['option3'] = 'Model 3';
$string['plan_correction'] = 'Plan correction:';
$string['planning_chat_placeholder'] = 'Describe the adjustments you want to make to the course planning...';
$string['planning_completed'] = 'Planning completed';
$string['pluginname'] = 'DataCurso';
$string['resource_created'] = 'Resource {$a} created successfully.';
$string['resourcecreatedsuccess'] = 'Resource created successfully.';
$string['semester_option1'] = 'I';
$string['semester_option10'] = 'X';
$string['semester_option2'] = 'II';
$string['semester_option3'] = 'III';
$string['semester_option4'] = 'IV';
$string['semester_option5'] = 'V';
$string['semester_option6'] = 'VI';
$string['semester_option7'] = 'VII';
$string['semester_option8'] = 'VIII';
$string['semester_option9'] = 'IX';
$string['send'] = 'Send';
$string['site'] = 'Site';
$string['syllabus_pdf_field'] = 'Upload Syllabus PDF';
$string['syllabus_pdf_field_help'] = 'Upload a PDF file containing the course syllabus. This will be sent to the AI for context analysis. Maximum file size: 10MB.';
$string['tenantid'] = 'Tenant ID';
$string['tenantid_desc'] = 'Enter your tenant ID here.';
$string['tenanttoken'] = 'Tenant Token';
$string['tenanttoken_desc'] = 'Enter your tenant token here.';
$string['training_objective_field'] = 'Define the TRAINING OBJECTIVE in this subject.';
$string['unauthorized'] = 'Unauthorized access';
$string['module_streaming_add_error'] = 'Could not add the activity to the course.';
$string['module_streaming_add_problem'] = 'There was a problem adding the activity: {$a}';
$string['module_streaming_added_success'] = '✅ Activity added to course correctly!';
$string['module_streaming_complete'] = '🎉 Your activity has been created successfully!';
$string['module_streaming_creation_error'] = '⚠️ An error occurred during activity creation';
$string['module_streaming_images_done'] = '✅ Images generated correctly';
$string['module_streaming_images_start'] = '🎨 Creating custom images...';
$string['module_streaming_output_start'] = '⚙️ Finalizing and preparing activity...';
$string['module_streaming_parameters_done'] = '✅ Configuration applied';
$string['module_streaming_parameters_start'] = '🔧 Applying activity configuration...';
$string['module_streaming_schema_done'] = '✅ Content structure ready';
$string['module_streaming_schema_start'] = '📋 Designing content structure...';
$string['module_streaming_start'] = '🚀 Starting activity creation...';
$string['module_creation_title'] = 'Creating module...';
$string['module_creation_subtitle'] = 'Please wait while the content is generated';
$string['yesimages'] = 'Generate images';
