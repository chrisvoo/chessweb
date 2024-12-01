# TODO

* [x] ~~Ability to read an .env file from backend/app/settings.php~~
* [ ] Define the database schema (might be useful depending what you can do on the hosting provder via SSH)
* [ ] Set up a DbManager class that has the Setting in the constructor and a getConnection 
method for accessing PDO. Evaluate using [Eloquent](https://codecourse.com/articles/using-eloquent-outside-of-laravel).
It's based on [illuminate/database](https://github.com/illuminate/database).
* [ ] See how PHPUnit works with Slim
* [ ] Set up PHPCS and PHPStan
* [ ] Explore the GitHUb pipeline for tests, linting and code coverage
* [ ] CRUD user entity
  * [ ] Read single user
  * [ ] Read all users
  * [ ] Create user
  * [ ] Update user
  * [ ] Delete user
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
* [ ] Logs rotation implementation
