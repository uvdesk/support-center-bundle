CHANGELOG for 1.1.x
===================

This changelog references any relevant changes introduced in 1.1 minor versions.

* 1.1.3.2 (2024-12-20)
    Updates:
    * Customer login using OTP functionality added.
    * Marketing Module added for marketing purpose.
    * License and support email related updates.
    * Code Refactor.
    
    Issue Fixes:
    * Issues #538 - Tag line is not translated in other languages except the english.
    * Made required message field in ticket creation form on front end.
    * Issues #228 - front website cookies policy Popup issue if switch language in arabic.
    * Issues #168 - Branding logo file type checking issue.
    * Issue #162  - Broadcast message when choosing current date, not showing current date.
    * Issue #215  - Article's numbered list not being rendered correctly.
    * Issue #218  - In Folder, if we using the any docs file in folder image file upload so here showing an error instead of warning.
    * Issue #174 - CSS Font-size 0 hides OL/UL/LI in Knowledgebase.
    * Issue #170 - When creating an article and adding a tag this special character: " / " after click on view button shows an error.
    * Issue #169 - When creating articles and adding tags should be show warning here: Must be less than 20 characters.
    * Issue #206 - Trashed ticket should not open on customer end.
    * Issue #216 - In article should be limit added for the horizontal lines.
    * Issue #173 - In article section when choosing Div and Pre tag so customer panel not showing text or content.
    * Marketing announcement URL validation added.
    * Allowing underscore in strong password for spacial characters.
    * setting customer last reply time.
    * Issue #245 - Update in customer dashboard footer content.

* 1.1.3.1 (2023-07-28)
    * PR #246: Update branding content in knowledgebase (Abhi12-gupta)

* 1.1.3 (2023-06-12)
    * Update: Dropped dependency on uvdesk/composer-plugin in support of symfony/flex
    * Update: Redefined workflow events & action, updated workflow triggers for improved compatibility support
    * PR #222: Trim whitespaces while updating spam settings (Abhi12-gupta)
    * PR #208: Add RTL support for supported locales (ar) (papnoisanjeev)

* 1.1.2.1 (2023-01-31)
    * Fixes: Resolve issues while saving custom fields on a ticket

* 1.1.2 (2022-11-02)
    * PR #210: Render ticket id on customer ticket view page (Komal-sharma-2712)

* 1.1.1 (2022-09-13)
    * Bug #193: Correctly enable/disable contact us banner on front website based on settings (vipin-shrivastava)
    * PR #193: Entity reference updates; Set default locale (vipin-shrivastava)
    * Bug #192: Remove old folder image from physical path if new folder image is uploaded (papnoisanjeev)
    * Bug #189: Resolve issue with wrong sort order on category listings and add support for managing enabled locale settings (vipin-shrivastava)
    * Bug #177: Display asterisk icons for required fields on customer create ticket form (Sanjaybhattwebkul)

* 1.1.0 (2022-03-23)
    * Feature: Improved compatibility with PHP 8 and Symfony 5 components
    * Bug #172: Update misc. validation error messages while working with knowledgebase categories and folders (vipin-shrivastava)
    * Bug #167: Add cross-site scripting checks for uploaded .svg assets (vipin-shrivastava)
    * Bug #166: Throw *NotFoundHttpException* instead to render *404 Page Not Found* error message when ticket is not found (vipin-shrivastava)
    * Bug #164: Fix misc. issues while updating articles (vipin-shrivastava)
    * Bug #163: Show warning when creating article with an already occupied slug name, update validation criteria when creating folders (vipin-shrivastava)
    * Bug #160: Delete previous website logo asset from filesystem while updating a new logo (vipin-shrivastava)
    * Bug #159: Delete knowledgebase folder image asset from file system when deleting folder (vipin-shrivastava)
    * Bug #158: Editor resize issue while creating ticket (vipin-shrivastava)
    * Bug #157: Use editor when creating ticket (vipin-shrivastava)
    * Bug #156: Article's numbered list not being rendered correctly (vipin-shrivastava)
    * Bug #155: Delete customer profile picture from file system when updating account details (papnoisanjeev)
    * Bug #154: Fix wrong custom field placeholder value being rendered (vipin-shrivastava)
