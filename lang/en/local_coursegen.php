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
 * @package     local_coursegen
 * @category    string
 * @copyright   2025 Josue Condori <https://datacurso.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accept_planning_create_course'] = 'Accept and create course';
$string['actions'] = 'Actions';
$string['addactivityai_arialabel'] = 'AI assistant to create resources/activities';
$string['addactivityai_done'] = 'Done! The resource/activity was created.';
$string['addactivityai_error'] = 'An error occurred while creating the resource. Please try again or modify the prompt.';
$string['addactivityai_faildefault'] = 'It was not possible to create the resource.';
$string['addactivityai_label'] = 'Describe what you need';
$string['addactivityai_modaltitle'] = 'Create resource/activity with AI';
$string['addactivityai_placeholder'] = 'Describe what resource or activity you want to create';
$string['addactivityai_welcome'] = 'Hi! Tell me what resource or activity you need and I will create it in your course. 😊';
$string['addactivitywithia'] = 'Add activity or resource with AI';
$string['addcourseai_arialabel'] = 'AI assistant to create courses';
$string['addcourseai_done'] = 'Done! The course was created successfully.';
$string['addcourseai_modaltitle'] = 'Create course with AI';
$string['addmodel'] = 'Add instructional model';
$string['adjust_course_planning'] = 'Adjust course planning';
$string['adjust_planning_title'] = 'Adjust course planning';
$string['click_to_view_details'] = 'Click to view details';
$string['confirmdelete'] = 'Are you sure you want to delete this model?';
$string['context_type_customprompt'] = 'Custom prompt';
$string['context_type_field'] = 'Context Type';
$string['context_type_model'] = 'Instructional Model';
$string['context_type_syllabus'] = 'Syllabus';
$string['course_created_success'] = 'Course created successfully!';
$string['course_created_success_simple'] = '✅ Course created successfully';
$string['course_creating_subtitle'] = 'Generating course content...';
$string['course_creating_title'] = 'Creating the course';
$string['course_planning_started'] = 'Course planning session started successfully.';
$string['course_streaming_loading'] = 'Loading...';
$string['course_streaming_planning_subtitle'] = 'Generating course content...';
$string['course_streaming_planning_title'] = 'Planning course creation';
$string['coursecreated'] = 'Course created successfully';
$string['coursegen:createactivitywithai'] = 'Create activities and resources with AI';
$string['coursegen:createcoursewithai'] = 'Create courses with AI';
$string['coursegen:managemodels'] = 'Manage instructional models';
$string['coursegen:view_syllabus'] = 'View syllabus';
$string['createwithai'] = 'Create with AI';
$string['creating_course'] = 'Creating Course...';
$string['custom_fields_header'] = 'DataCurso';
$string['custom_model_select_field'] = 'Choose the model to use';
$string['custom_prompt_field'] = 'Prompt for AI';
$string['custom_prompt_field_help'] = 'Describe the course context you want the AI to use. You can use formatting and media to enrich the instructions.';
$string['delete'] = 'Delete';
$string['deletemodel'] = 'Delete Model';
$string['edit'] = 'Edit';
$string['editmodel'] = 'Edit Model';
$string['enter_message'] = 'Enter your message';
$string['error'] = 'Error: {$a}';
$string['error_context_type_required'] = 'Please select a context type to continue (Instructional model, Syllabus or Custom prompt).';
$string['error_creating_course'] = '❌ Error creating course: {$a}';
$string['error_executing_plan'] = 'Error executing the course plan';
$string['error_generating_resource'] = 'There was a problem generating the requested resource. Please try again later.';
$string['error_invalid_resource_type'] = 'Could not find a valid resource type from AI response: {$a}. Please try again.';
$string['error_label'] = 'Error';
$string['error_missing_parameters'] = 'Could not get parameters to create the module from the AI response. Please try again.';
$string['error_missing_resource_type'] = 'Could not get the resource type from the AI response. Please try again.';
$string['error_model_required'] = 'Please select an instructional model when the context type is "Instructional model".';
$string['error_no_course_id'] = 'Could not get course ID';
$string['error_no_models_configured'] = 'There are no instructional models configured. Please create models first in the Manage models page.';
$string['error_no_session_found'] = 'No planning session found for this course and user.';
$string['error_processing_request'] = 'Error processing your request';
$string['error_prompt_required'] = 'You must provide a prompt when the context type is "Custom prompt".';
$string['error_saving_session'] = 'Failed to save the planning session. Please try again.';
$string['error_sending_message'] = 'Error sending message';
$string['error_starting_course_planning'] = 'There was an error starting the course planning. Please try again';
$string['error_syllabus_pdf_required'] = 'You must upload a Syllabus PDF when the context type is "Syllabus".';
$string['error_upload_failed'] = 'Failed to upload syllabus: {$a}';
$string['error_upload_failed_model'] = 'Failed to upload model: {$a}';
$string['execution_activity_done'] = '✅ Activity completed ({$a->done}/{$a->total}) — {$a->percent}%';
$string['execution_activity_start'] = '🧩 Starting activity #{$a->index} (section {$a->section}): {$a->title}';
$string['execution_error_activity'] = '❌ Error in an activity';
$string['execution_progress'] = '📈 Progress: {$a->done}/{$a->total} ({$a->percent}%)';
$string['generalsettings'] = 'General settings';
$string['invalidmodel'] = 'Invalid model';
$string['managemodels'] = 'Manage instructional models';
$string['message_sent_successfully'] = 'Message sent successfully to AI planning session.';
$string['modelcontent'] = 'Model content';
$string['modelcontent_help'] = 'Provide the full description of the instructional model, including its phases/steps, purpose, and how it should guide course design.';
$string['modelcreated'] = 'Created';
$string['modeldeleted'] = 'Model deleted successfully';
$string['modelmodified'] = 'Modified';
$string['modelname'] = 'Model name';
$string['modelname_help'] = 'Enter the name of the instructional model you will use for course design. For example: ADDIE, Gagne, or another recognized framework.';
$string['modelnameexists'] = 'A model with this name already exists';
$string['modelsaved'] = 'Model saved successfully';
$string['module_creation_subtitle'] = 'Please wait while the content is generated';
$string['module_creation_title'] = 'Creating module...';
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
$string['no_models_configured_notice'] = 'To use this context type, you must create instructional models first. Go to the <a href="{$a}">Manage models</a> page.';
$string['noimages'] = 'Do not generate images';
$string['nomodels'] = 'No models found';
$string['planning_chat_placeholder'] = 'Describe the adjustments you want to make to the course planning...';
$string['planning_completed'] = 'Planning completed';
$string['pluginname'] = 'Course Creator AI';
$string['privacy:metadata:local_coursegen'] = 'Course Creator AI stores personal data to support AI-driven course and module generation.';
$string['privacy:metadata:local_coursegen_course_context'] = 'Context preferences stored for course planning.';
$string['privacy:metadata:local_coursegen_course_context:context_type'] = 'The selected context type for the course.';
$string['privacy:metadata:local_coursegen_course_context:courseid'] = 'The ID of the course whose context preferences are stored.';
$string['privacy:metadata:local_coursegen_course_context:model_id'] = 'The selected instructional model ID for the course.';
$string['privacy:metadata:local_coursegen_course_context:prompt_text'] = 'The custom prompt text provided for the course.';
$string['privacy:metadata:local_coursegen_course_context:timecreated'] = 'The time when the course context record was created.';
$string['privacy:metadata:local_coursegen_course_context:timemodified'] = 'The time when the course context record was last updated.';
$string['privacy:metadata:local_coursegen_course_context:usermodified'] = 'The ID of the user who last modified the course context.';
$string['privacy:metadata:local_coursegen_course_data'] = 'Custom course data stored for integration with DataCurso.';
$string['privacy:metadata:local_coursegen_course_data:courseid'] = 'The ID of the course this data belongs to.';
$string['privacy:metadata:local_coursegen_course_data:custom_checkbox'] = 'A custom checkbox value associated with the course.';
$string['privacy:metadata:local_coursegen_course_data:custom_date'] = 'A custom date value associated with the course.';
$string['privacy:metadata:local_coursegen_course_data:custom_select'] = 'A custom select value associated with the course.';
$string['privacy:metadata:local_coursegen_course_data:custom_text'] = 'A custom short text value associated with the course.';
$string['privacy:metadata:local_coursegen_course_data:custom_textarea'] = 'A custom textarea value associated with the course.';
$string['privacy:metadata:local_coursegen_course_data:timecreated'] = 'The time when the record was created.';
$string['privacy:metadata:local_coursegen_course_data:timemodified'] = 'The time when the record was last updated.';
$string['privacy:metadata:local_coursegen_course_sessions'] = 'Course planning sessions created through the AI service.';
$string['privacy:metadata:local_coursegen_course_sessions:courseid'] = 'The ID of the course being planned.';
$string['privacy:metadata:local_coursegen_course_sessions:session_id'] = 'The session identifier returned by the AI service.';
$string['privacy:metadata:local_coursegen_course_sessions:status'] = 'The current status of the course planning session.';
$string['privacy:metadata:local_coursegen_course_sessions:timecreated'] = 'The time when the course planning session was created.';
$string['privacy:metadata:local_coursegen_course_sessions:timemodified'] = 'The time when the course planning session was last updated.';
$string['privacy:metadata:local_coursegen_course_sessions:userid'] = 'The ID of the user who initiated the course planning session.';
$string['privacy:metadata:local_coursegen_model'] = 'Instructional models created or edited for course generation.';
$string['privacy:metadata:local_coursegen_model:content'] = 'The stored description of the instructional model.';
$string['privacy:metadata:local_coursegen_model:deleted'] = 'Whether the instructional model is marked as deleted.';
$string['privacy:metadata:local_coursegen_model:name'] = 'The name of the instructional model.';
$string['privacy:metadata:local_coursegen_model:timecreated'] = 'The time when the instructional model was created.';
$string['privacy:metadata:local_coursegen_model:timemodified'] = 'The time when the instructional model was last updated.';
$string['privacy:metadata:local_coursegen_model:usermodified'] = 'The ID of the user who last modified the instructional model.';
$string['privacy:metadata:local_coursegen_module_jobs'] = 'Details of AI module generation jobs queued by users.';
$string['privacy:metadata:local_coursegen_module_jobs:beforemod'] = 'The module ID before which the new activity should be inserted.';
$string['privacy:metadata:local_coursegen_module_jobs:context_type'] = 'The context type provided when running the module job.';
$string['privacy:metadata:local_coursegen_module_jobs:courseid'] = 'The ID of the course associated with the module job.';
$string['privacy:metadata:local_coursegen_module_jobs:generate_images'] = 'Whether the module job will generate images.';
$string['privacy:metadata:local_coursegen_module_jobs:job_id'] = 'The identifier returned by the AI service for the module job.';
$string['privacy:metadata:local_coursegen_module_jobs:model_name'] = 'The instructional model name provided to the AI service.';
$string['privacy:metadata:local_coursegen_module_jobs:sectionnum'] = 'The course section number where the activity should be created.';
$string['privacy:metadata:local_coursegen_module_jobs:status'] = 'The current status of the module job.';
$string['privacy:metadata:local_coursegen_module_jobs:timecreated'] = 'The time when the module job was created.';
$string['privacy:metadata:local_coursegen_module_jobs:timemodified'] = 'The time when the module job was last updated.';
$string['privacy:metadata:local_coursegen_module_jobs:userid'] = 'The ID of the user who started the module job.';
$string['resource_created'] = 'Resource {$a} created successfully.';
$string['send'] = 'Send';
$string['syllabus_pdf_field'] = 'Upload Syllabus PDF';
$string['syllabus_pdf_field_help'] = 'Upload a PDF file containing the course syllabus. This will be sent to the AI for context analysis. Maximum file size: 10MB.';
$string['unauthorized'] = 'Unauthorized access';
$string['yesimages'] = 'Generate images';
