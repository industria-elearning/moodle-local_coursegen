# Course Creator AI

The **Datacurso Course Creator AI** plugin empowers Moodle teachers to **generate complete courses automatically using artificial intelligence**.  
It offers two flexible creation modes — via a **syllabus** or through an **instructional design model** — allowing teachers to build structured, pedagogically sound courses in minutes.

This plugin also enables the **independent creation of activities** within any existing course, giving educators the ability to enhance their classes with AI-generated learning activities at any time.

This plugin is part of the suite of **Datacurso AI Plugin Suite**.

## The Datacurso AI Plugin Suite

Transform Moodle into a **smarter, faster, and more engaging learning platform** with the **Datacurso AI Plugin Suite** — a collection of next-generation tools that bring artificial intelligence directly into your LMS.  
All plugins in this suite are powered by the **Datacurso AI Provider**.

### Explore the Suite

- **[Ranking Activities AI](https://moodle.org/plugins/local_datacurso_ratings)**
  Empower students to rate course activities while AI analyzes feedback and provides deep insights to educators.

- **[Forum AI](https://moodle.org/plugins/local_forum_ai)**  
  Introduce an AI assistant into your forums that contributes to discussions and keeps engagement alive.

- **[Assign AI](https://moodle.org/plugins/local_assign_ai)**  
  Let AI review student submissions, suggest feedback, and support teachers in the grading process.

- **[Share Certificate AI](https://moodle.org/plugins/local_socialcert)**  
  Celebrate achievements automatically! AI generates personalized social media posts when students earn certificates.

- **[Student Life Story AI](https://moodle.org/plugins/report_lifestory)**  
  Gain a complete view of student performance with AI-generated summaries across all enrolled courses.

## Key Features

- **Full Course Generation:** Create entire Moodle courses automatically based on a syllabus or instructional model.  
- **AI-Powered Activities:** Generate interactive, engaging activities that align with your course objectives.  
- **Instructional Flexibility:** Combine AI creativity with structured educational models to ensure quality learning experiences.  
- **Independent Activity Creation:** Add new AI-generated activities to any course on demand. 


## Pre-requisites

1. Moodle 4.5
2. Install the Moodle AI provider **DataCurso AI Provider**. Download it for free from [https://moodle.org/plugins/aiprovider_datacurso/versions](https://moodle.org/plugins/aiprovider_datacurso/versions).
3. In the DataCurso AI Provider settings, configure a valid license key as documented at [https://docs.datacurso.com/index.php?title=Datacurso_AI_Provider#Getting_license_keys](https://docs.datacurso.com/index.php?title=Datacurso_AI_Provider#Getting_license_keys).

### IMPORTANT
This plugin will not function unless the **DataCurso AI Provider** plugin is installed and licensed.

## Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to `Site administration > Plugins > Install plugins`.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually

The plugin can be also installed by putting the contents of this directory to

`{your/moodle/dirroot}/local/coursegen`

Afterwards, log in to your Moodle site as an admin and go to `Site administration > Notifications` to complete the installation.

Alternatively, you can run

```bash
php admin/cli/upgrade.php
```

to complete the installation from the command line.

## Manage instructional models

With instructional models, you can define the structure and content of your courses.
They serve as reusable blueprints (e.g., ADDIE, Gagné) that guide the AI during planning and generation, specifying phases, steps, and constraints the assistant should follow when producing sections, activities, and content.

To manage instructional models:

1. Log in to your Moodle site as an admin and go to `Site administration > Plugins > Course Creator AI > Manage models`.
   
  ![Manage models](./_docs/images/local_coursegen_manage_models.png)

1. Click on `Add new model` to create a new model.

  ![Add model](./_docs/images/local_coursegen_add_model.png)

2. Fill in the form fields:
   - **Name**: Enter the name of the instructional model you will use for course design. For example: ADDIE, Gagne, or another recognized framework.
   - **Description**: Provide the full description of the instructional model, including its phases/steps, purpose, and how it should guide course design.
   - Click on `Save changes`.

  ![Model form](./_docs/images/local_coursegen_model_form.png)

3. You can edit or delete models at any time with the `Edit` and `Delete` buttons from the ***Manage models*** page.

  ![Edit or delete models](./_docs/images/local_coursegen_edit_delete_models.png)


## Create a Course with Datacurso AI

Follow these steps to create a new course using the Datacurso AI workflow:

### Open the course creation page
- **Path A**: `Site administration > Courses > Manage courses and categories` and click the **Create new course** button.

    ![Create course path A](./_docs/images/local_coursegen_create_course_path_a.png)

- **Path B**: From your **My courses area**, click the **Create course** button.

    ![Create course path B](./_docs/images/local_coursegen_create_course_path_b.png)

### Fill in basic course details
- Complete standard fields like `Course full name`, `Course short name`, `Course category`, and any other required fields.

  ![Fill in basic course details](./_docs/images/local_coursegen_fill_in_basic_course_details.png) 

### Configure the Datacurso section
- In the `Datacurso` section, select the **Context Type**:
  - **Instructional Model** (default): Choose from models created in the section [Manage instructional mode](#manage-instructional-models). A selector labeled **Choose the model to use** will appear, listing yo available models.
  
    ![Instructional Model](./_docs/images/local_coursegen_instructional_model.png) 
  
  - **Upload Syllabus (PDF)**: Switch to this option to show a file picker labeled **Upload Syllabus PDF** a upload your syllabus.
  
    ![Upload Syllabus](./_docs/images/local_coursegen_upload_syllabus.png) 
      

### Plan with AI
- Click **Create with AI** to start the AI planning process.

  ![Create with AI button](./_docs/images/local_coursegen_create_with_ai_button.png)

- A modal window will open and display the planning progress.

  ![Planning progress](./_docs/images/local_coursegen_planning_progress.png)

- Once the plan is generated, you can optionally adjust it by clicking **Adjust course planning**, then provide a prompt with your instructions to re-plan.

  ![Adjust course planning](./_docs/images/local_coursegen_adjust_course_planning.png)

- The progress of the plan adjustment will be displayed in the modal window.

  ![Plan adjustment progress](./_docs/images/local_coursegen_plan_adjustment_progress.png)

### Create the course
- When the planning looks good, click **Accept and create course**.

    ![Accept and create course](./_docs/images/local_coursegen_accept_and_create_course.png)

- This starts creating the course with planned content.
- The modal will show real-time updates for each phase of the process.

    ![Creating course](./_docs/images/local_coursegen_creating_course.png)

- Once the process is completed, it redirects to the created course.

    ![Created course](./_docs/images/local_coursegen_created_course.png)

### IMPORTANT!
- Do not close the modal window during the planning or creation process to avoid issues with course creation.

## Create an Activity with Datacurso AI

### Check the Datacurso context

Before using the AI activity creator, confirm that the course already has a **Datacurso context** defined.

If the course **already has a context** (for example, an *Instructional Model* or an *uploaded Syllabus PDF*), the AI will automatically use it to generate the activity.

If the course **does not have a context yet**, you can set it from the course settings:

1. Open the course and click **Edit settings**.

![Edit settings](./_docs/images/local_coursegen_edit_settings.png)

3. Go to the **Datacurso** section.  
4. In **Context Type**, choose one of the following options:

  - **Instructional Model** (default): Choose from models created in the section [Manage instructional mode](#manage-instructional-models). A selector labeled **Choose the model to use** will appear, listing yo available models.
  
    ![Instructional Model](./_docs/images/local_coursegen_instructional_model.png) 
  
  - **Upload Syllabus (PDF)**: Switch to this option to show a file picker labeled **Upload Syllabus PDF** a upload your syllabus.
  
    ![Upload Syllabus](./_docs/images/local_coursegen_upload_syllabus.png)  

Once the Datacurso context is set at the course level, it will be automatically reused for all AI-generated activities within that course.

### Start the activity creation

- Enter to the course view and turn the **Edit mode** on.

    ![Edit mode](./_docs/images/local_coursegen_edit_mode.png) 

- Next to the standard **Add activity or resource** button, click the new button **Add activity or resource with AI**.

    ![Add activity or resource with AI](./_docs/images/local_coursegen_add_activity_or_resource_with_ai.png)

### Provide the activity prompt
- A modal window with a chat input will open. Enter a clear prompt describing the activity you want to create (e.g., type of activity, learning objectives, instructions, etc.).

    ![Activity prompt](./_docs/images/local_coursegen_activity_prompt.png)

### Optional: Images in the activity
- Use the combobox to choose whether the activity should include images. By default this is set to **No not generate images**.

    ![Images in the activity](./_docs/images/local_coursegen_images_in_the_activity.png)
 
- If you want to generate images, select **Generate images**.

    ![Generate images](./_docs/images/local_coursegen_generate_images.png)

### Start creation
- Press **Enter** in the text field or click the **Send** icon button to begin creating the activity with AI.

    ![Start creation](./_docs/images/local_coursegen_start_creation.png)

- The modal will display real-time progress updates for each phase of the process.

    ![Progress updates](./_docs/images/local_coursegen_progress_updates.png)

- Once the process is completed, it redirects to the created activity.

    ![Created activity](./_docs/images/local_coursegen_created_activity.png)

### IMPORTANT!
- Do not close the modal window until the process has fully completed to avoid issues with activity creation.

## License

2025 Data Curso LLC <https://datacurso.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
