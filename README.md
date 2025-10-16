# Course Creator AI

The **Datacurso Course Creator AI** plugin empowers Moodle teachers to **generate complete courses automatically using artificial intelligence**.  
It offers two flexible creation modes — via a **syllabus** or through an **instructional design model** — allowing teachers to build structured, pedagogically sound courses in minutes.

This plugin also enables the **independent creation of activities** within any existing course, giving educators the ability to enhance their classes with AI-generated learning activities at any time.

This plugin is part of the suite of **Datacurso AI Plugin Suite**.

## The Datacurso AI Plugin Suite

Transform Moodle into a **smarter, faster, and more engaging learning platform** with the **Datacurso AI Plugin Suite** — a collection of next-generation tools that bring artificial intelligence directly into your LMS.  
All plugins in this suite are powered by the **Datacurso AI Provider**.

### Explore the Suite

- **[Ranking Activities AI](#)**
  Empower students to rate course activities while AI analyzes feedback and provides deep insights to educators.

- **[Forum AI](#)**  
  Introduce an AI assistant into your forums that contributes to discussions and keeps engagement alive.

- **[Assign AI](#)**  
  Let AI review student submissions, suggest feedback, and support teachers in the grading process.

- **[Tutor AI](#)**  
  Offer students a personal AI tutor that answers questions, explains concepts, and guides them through their learning path.

- **[Share Certificate AI](#)**  
  Celebrate achievements automatically! AI generates personalized social media posts when students earn certificates.

- **[Student Life Story AI](#)**  
  Gain a complete view of student performance with AI-generated summaries across all enrolled courses.

- **[Course Creation AI](#)**  
  Build full Moodle courses in minutes — complete with lessons, activities, and resources — guided by AI.

- **[Activity Creation AI](#)**  
  Design engaging, high-quality learning activities instantly using AI-generated suggestions and templates.

## Key Features

- **Full Course Generation:** Create entire Moodle courses automatically based on a syllabus or instructional model.  
- **AI-Powered Activities:** Generate interactive, engaging activities that align with your course objectives.  
- **Instructional Flexibility:** Combine AI creativity with structured educational models to ensure quality learning experiences.  
- **Independent Activity Creation:** Add new AI-generated activities to any course on demand. 


## Pre-requisites

1. Moodle 4.5
2. Install the Moodle AI provider **DataCurso AI Provider**. Download it for free from [https://moodle.org/plugins/aiprovider_datacurso/versions](https://moodle.org/plugins/aiprovider_datacurso/versions).
3. In the DataCurso AI Provider settings, configure a valid license key as documented at [https://docs.datacurso.com/index.php?title=Datacurso_AI_Provider#Getting_license_keys](https://docs.datacurso.com/index.php?title=Datacurso_AI_Provider#Getting_license_keys).

**IMPORTANT**: This plugin will not function unless the **DataCurso AI Provider** plugin is installed and licensed.

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
