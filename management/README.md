# Readme

This project provides a CLI application written in PHP for doing
the following tasks:
* migration of the old content to the new website
* deploy the resources to the specified server

## Deployment

In order to correctly deploy the files, you must execute:

```
./bin/chess deploy -e <environment> <type>
```
`<type>`: it may be `frontend`, `backend` or `both` (default)  
`--dry-run`: it will print the operation it will do without doing them  
`-e|--environment`: may be `local` or `remote`. You must define
the environment variables in an .env file placed in the root of this
project following the examples contained in `.env.dist`.  
For example you can `cp .env.dist .env` and then edit its content. The
command will take care of:
* building the Angular project (frontend)
* copying the backend files
* copying the resources

For apache, the minimal configuration is the following:

```
<VirtualHost *:8080>
    ServerAdmin webmaster@dummy-host.example.com
    DocumentRoot "/opt/homebrew/var/www/scacchilatorre"

	<Directory /opt/homebrew/var/www/scacchilatorre>
		Options Indexes FollowSymLinks
    	AllowOverride All
	    Require all granted
    </Directory>

    ServerName scacchilatorre.dev
    ServerAlias www.scacchilatorre.dev
    ErrorLog "/opt/homebrew/var/log/httpd/error_log"
    CustomLog "/opt/homebrew/var/log/httpd/access_log" common
</VirtualHost>
```

for NGINX:

```
  root "{{root}}/public"; 
  index index.html index.php;
  
  location / {
    try_files $uri $uri/ /index.php?$query_string;
  }
```

For the manual deployment, assuming `/opt/homebrew/var/www/scacchilatorre` is the public directory (we will
refer to it with ROOT_DIR), you need to:
1. for the backend project, move all the files inside `ROOT_DIR`
2. build the frontend project (`ng build`) and copy the files from
`/dist/browser/*` to `ROOT_DIR/public`

For the backend, you can omit all the file with the following extensions:
* `.md`
* `.xml`
* `.back`
* `.yml`
* the directories `tests`, `logs`

The directory vendor can be omitted if you have SSH access and have
composer installed in the user terminal.
