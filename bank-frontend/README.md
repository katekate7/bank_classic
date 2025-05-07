## Deploy localy
Install dependencies and build the app for production
```
npm install
npm run build
```

If you want to start the project localy
```
npm run start
```

## Deploy with Docker

Install dependencies and build the app for production
```
npm install
npm run build
```

Build the image and run as container
```
docker build -t bank_front .
docker run --name bank_front_container -p 3000:3000 bank_front
```

## Deploy with Jenkins

If not already done start an instance of jenkins_master
```
docker run --name jenkins -p <choose_a_port>:8080 jenkins/jenkins
```

Then build and start an instance of a jenkins_agent
If your are on Windows, execute this command in Powershell or cmd
```
cd Jenkins-agent
docker build -t jenkins-agent-bank_front .
```

To get the Jenkins master IP adress
```
docker inspect jenkins
```

Link the jenkins agent
```
docker run --init --name jenkins_agent_react_bank -v /var/run/docker.sock:/var/run/docker.sock jenkins-agent-with-docker-and-react-bank -url http://172.17.0.2:8080 2f98b0f2bb6c7f1e418c93b3745a43d916f4cb2cee579ddded716a4668efc1c0 mybank-frontend
``