# Frontend Migration Progress (PHP to Pure HTML/CSS/JS)

## Objective
Convert the project's frontend stack to pure HTML, CSS, and JS for hosting on Vercel as a static frontend (with PHP APIs handling the backend logic).

## Approach
1.  **Componentization**: Create a JavaScript loader (`js/components.js`) to dynamically inject the header and footer into all HTML pages.
2.  **API Extraction**: Move inline PHP database queries into dedicated JSON endpoints in the `api/public/` directory.
3.  **Page Conversion**: Rewrite `.php` pages as `.html` and replace PHP logic with JavaScript `fetch()` calls.

## Current Phase: Public Pages First

### Completed
- [x] Created `js/components.js` to load header and footer dynamically.
- [x] Converted `includes/header.php` to `components/header.html`.
- [x] Converted `includes/footer.php` to `components/footer.html`.
- [x] Converted `index.php` to `index.html` and extracted `api/public/activities.php`.
- [x] Converted `about.php` to `about.html`.
- [x] Converted `contact.php` to `contact.html` and extracted `api/public/contact.php`.
- [x] Converted `membership.php` to `membership.html`.
- [x] Converted `fees.php` to `fees.html` and extracted `api/public/settings.php`.
- [x] Converted `login.php` to `login.html` (Uses existing `api/v2/login.php`).
- [x] Converted `register.php` to `register.html` and extracted `api/public/register.php`.
- [x] Converted `activities.php` to `activities.html`.
- [x] Converted `department.php` and `staff_profile.php` to HTML, extracting `api/public/department.php`.
- [x] Converted `events.php` to `events.html` and extracted `api/public/events.php`.
- [x] Converted `executives.php` to `executives.html` and extracted `api/public/executives.php`.
- [x] Converted `projects.php` to `projects.html` and extracted `api/public/projects.php`.
- [x] Converted `news.php` to `news.html` and extracted `api/public/news.php`.
- [x] Converted `alumni.php` to `alumni.html` and extracted `api/public/alumni.php`.
- [x] Converted `gallery.php` to `gallery.html` and extracted `api/public/gallery.php`.
- [x] Converted `resources.php` to `resources.html` and extracted `api/public/resources.php`.
- [x] Converted all student portal pages (`dashboard`, `profile`, `history`, `messages`, `password-reset`) to HTML/JS and created corresponding APIs.
- [x] Improved CSS for mobile responsiveness and fixed `box-sizing` typo.

### Next Steps
- Validate all student portal pages in the browser.
- Convert admin dashboard pages.
