# Moodle Block: Dashboard Data Visualization

[![Moodle Version](https://img.shields.io/badge/moodle-4.4+-blue.svg)](https://moodle.org/)
[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)

A comprehensive, visually rich dashboard block for Moodle that provides learners with deep insights into their learning habits, progress, and performance. 

This block aggregates data directly from Moodle's core tables (logs, grades, enrolments, completions) to generate engaging metrics without requiring any custom database tables or complex installation processes.

---

## 🌟 Features

*   **Key Performance Indicators (KPIs):**
    *   **Engagement Score:** A composite score (0-100) based on activity volume, completion rates, and average grades, complete with month-over-month trend indicators.
    *   **Average Grade:** The user's overall grade percentage across all active courses.
    *   **Courses Completed:** Quick view of completion rates.
    *   **Study Sessions:** Number of distinct study sessions (groups of actions within 30 minutes) this month.
    *   **Learning Streak:** Number of consecutive days the user has been active on the platform.
*   **Interactive Charts (Powered by Chart.js / Moodle core_chart):**
    *   **Engagement Over Time:** A 6-month historical line chart of the user's engagement score.
    *   **Completed & In Progress by Subject:** Bar charts categorizing course enrolments by Moodle Course Categories.
    *   **Actions Breakdown:** A doughnut chart classifying user logs into Learning, Viewing, Assessments, and Other.
    *   **Study Consistency:** A bar chart showing study sessions per day of the week to highlight learning habits.
    *   **Top Subjects & Performance:** Bar charts ranking course categories by time spent and average grade.
*   **Insights & Tables:**
    *   **Strengths & Areas to Improve:** Automatically highlights the user's top 3 and bottom 3 performing subjects.
    *   **Course Progress Table:** A detailed list of all enrolled courses with progress bars, current grades, last activity timestamps, and completion status badges.
*   **Highly Configurable:** Every single widget, chart, and KPI can be toggled on or off, reordered, and (for the chart panels) resized from the block's settings. Admins can also pick a custom accent color for the line and bar charts using a native color picker.

## ⚙️ Requirements

*   **Moodle version:** 4.4 or higher
*   **Logging:** Moodle's standard logging (`logstore_standard_log`) MUST be enabled for the engagement, streak, and session metrics to function correctly.
*   **Course Completion:** Course completion tracking should be enabled at the site and course level for accurate completion metrics.

## 🚀 Installation

### Method 1: Git
1. Navigate to your Moodle blocks directory:
   ```bash
   cd /path/to/moodle/blocks/
   ```
2. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/moodle-block_dashboard_data_visualization.git dashboard_data_visualization
   ```
3. Log in to Moodle as an administrator and go to **Site Administration > Notifications** to complete the installation.

### Method 2: ZIP Download
1. Download the repository as a ZIP file.
2. Extract the contents and rename the folder to `dashboard_data_visualization`.
3. Upload the folder to `/blocks/` in your Moodle installation.
4. Log in to Moodle as an administrator and go to **Site Administration > Notifications** to complete the installation.

## 🎨 Configuration & Usage

Once installed, the block is designed to be placed on the user Dashboard (`/my/`).

1. Log in to Moodle and navigate to the Dashboard.
2. Turn **Edit mode** on.
3. Click **Add a block** and select **Dashboard Data Visualization**.
4. Click the gear icon on the newly added block and select **Configure Dashboard Data Visualization block**.
5. From the settings form, you can:
   *   **KPI cards** — toggle the visibility of any individual KPI card and set its **order**.
   *   **Charts & panels** — toggle the visibility of any chart or table, set its **order**, and choose its **width** (quarter, third, half, two-thirds, or full width of the 12-column grid).
   *   **Change the Chart Color:** Use the color picker to select a custom hex color for the bar and line charts to match your theme.
6. Save changes.

### Ordering and widths

Sections are rendered from the lowest order number to the highest. Defaults are spaced in tens
(10, 20, 30, …) so a section can be moved between two others without renumbering everything —
give it any value in between. Sections that end up with the same order number keep their default
relative position.

Panel widths are expressed in twelfths of the block's width, so a row "adds up" when the widths of
the panels on it total 12 (for example half + quarter + quarter). Panels that do not fit simply wrap
onto the next row, and the grid collapses to fewer columns automatically in narrow block regions.

## 🛠️ Architecture

*   **No Custom DB Tables:** The block does not create or rely on custom tables (`install.xml`). It efficiently queries `logstore_standard_log`, `course_completions`, `grade_grades`, and `user_enrolments`.
*   **Cross-Database Compatible:** All SQL queries strictly adhere to Moodle's cross-db syntax. Date and time manipulations are performed in PHP (e.g., extracting days of the week, month boundaries) to avoid DB-specific SQL functions.
*   **Modular Rendering:** Follows Moodle's recommended architecture using a `data_provider` class for data fetching, an `output\main` renderable class for logic, and Mustache templates for presentation.

## 📝 License

This plugin is licensed under the [GNU General Public License v3 (GPL-3.0)](http://www.gnu.org/copyleft/gpl.html).

---
*Created for LAL Moudaress.*
