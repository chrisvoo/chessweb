# TODO

* [x] ~~Ability to read an .env file from backend/app/settings.php~~ (12/1/2024)
* [x] ~~Define the database schema (might be useful depending what you can do on the hosting provider via SSH)~~ (12/1/2024)
* [x] ~~Set up a DbManager class~~ (12/1/2024)
* [x] ~~See how PHPUnit works with Slim~~ (12/4/2024)
* [ ] Set up PHPCS and PHPStan
* [ ] Explore the GitHUb pipeline for tests, linting and code coverage
* [x] CRUD user entity
  * [x] ~~Read single user~~ (12/4/2024)
  * [x] ~~Read all users~~ (12/8/2024)
  * [x] ~~Create user~~ (12/8/2024)
  * [x] ~~Update user~~ (12/8/2024)
  * [x] ~~Delete user~~ (12/8/2024)
* [ ] CRUD article entity
  * [ ] Read single article
  * [ ] Read all articles with filters, pagination and sorting
  * [ ] Create article
  * [ ] Update article
  * [ ] Delete article
* [ ] CRUD tag entity
  * [ ] Read single tag
  * [ ] Read all tags
  * [ ] Create tag
  * [ ] Update tag
  * [ ] Delete tag
* [ ] CRUD category entity
  * [ ] Read single category
  * [ ] Read all categories
  * [ ] Create category
  * [ ] Update category
  * [ ] Delete category
* [ ] Authentication middleware. We could implement a JWT access token in memory and a refresh token in cookie.  
See https://www.cyberchief.ai/2023/05/secure-jwt-token-storage.html
* [ ] Logs rotation implementation: https://stackoverflow.com/questions/55369654/how-to-set-max-size-for-log-file-using-monolog
