## Deploy localy
Install the dependances
```
composer install
```

Create the database and make the migrations
```
symfony console doctrine:database:create
symfony console doctrine:migration:migrate
```

Try locally
```
symfony serve
```

## Deploy with Docker

Create a network
```
docker network create bank-network
```

If needed deploy a myslq container
```
docker run --name bank-mysql --network bank-network -p 3306:3306 -e MYSQL_ROOT_PASSWORD=root mysql
```

Change the connection string in the .env line 27 with the container name of mysql container

Build the image and deploy as container
```
docker build . -t bank-backend
docker run --name bank-backend_container --network bank-network -p 8089:80 bank-backend
```

Create database in mysql container and make the migration
```
docker exec -it bank-backend_container php bin/console doctrine:database:create
docker exec -it bank-backend_container php bin/console doctrine:migration:migrate
```

## Deploy with Jenkins

If not already done start an instance of jenkins_master
```
docker run --name jenkins -p <choose_a_port>:8080 jenkins/jenkins
```

Then build and start an instance of a jenkins_agent
If your are on Windows, execute this command in Powershell or cmd
```
cd Jenkins-agent
docker build -t jenkins-agent-with-docker-and-composer-bank .
docker run --init --name jenkins_agent_bank-back-composer -v /var/run/docker.sock:/var/run/docker.sock jenkins-agent-with-docker-and-composer-bank -url http://172.17.0.2:8080 36ee2edcf63887bfc8056302eb0d9b213c183f3b5859970bdc6ba093ed0c23b6 mybank_symfony_agent
```
