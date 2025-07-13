| Page/Component        | URL                     | Type      | Roles    | Permission(s)                              |
| --------------------- | ----------------------- | --------- | -------- | ------------------------------------------ |
| Dashboard             | /dashboard              | Page      | all      | view calendar, view own/all calendar       |
| Lesson Detail Modal   | (modal from calendar)   | Modal     | all      | book lesson, cancel booking, manage lesson |
| Lesson Detail Page    | /lessons/{id}           | Page      | all      | same as above                              |
| Profile               | /profile                | Page      | all      | edit own profile                           |
| My Packages           | /my-packages            | Page      | client   | view my packages                           |
| My Bookings           | /my-bookings            | Page      | client   | view my bookings                           |
| My Digital Lessons    | /my-digital-lessons     | Page      | client   | view my digital lessons                    |
| Operator Availability | /operator/availability  | Page      | operator | edit own availability                      |
| Operator Lessons      | /operator/lessons       | Page      | operator | view/add/cancel own lessons                |
| Admin Users           | /admin/users            | CRUD Page | admin    | manage users                               |
| Admin Packages        | /admin/packages         | CRUD Page | admin    | manage packages                            |
| Admin Lessons         | /admin/lessons          | CRUD Page | admin    | manage lessons                             |
| Admin Machines        | /admin/machines         | CRUD Page | admin    | manage machines                            |
| Admin Rooms           | /admin/rooms            | CRUD Page | admin    | manage rooms                               |
| Admin Digital Lessons | /admin/digital-lessons  | CRUD Page | admin    | manage digital lessons                     |
| Login/Register        | /login, /register, etc. | Auth Page | guest    | -                                          |
| Instructor Profile    | /instructor/{id}        | Page      | all      | view operator profile                      |
| Room Detail           | /room/{id}              | Page      | all      | view room                                  |
| Public Calendar       | /calendar/public        | Page      | guest    | -                                          |
