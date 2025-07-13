pipeline {
    agent any

    stages {
        stage('Build Frontend') {
            steps {
                dir('bank-frontend') {
                    sh 'docker build -t bank_frontend .'
                }
            }
        }

        stage('Build Backend') {
            steps {
                dir('bank-backend') {
                    sh 'docker build -t bank_backend .'
                }
            }
        }
    }
}
