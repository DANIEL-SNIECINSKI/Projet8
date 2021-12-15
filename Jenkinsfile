pipeline {
  agent any
  stages {
    stage('deps') {
      steps {
        sh 'COMPOSER_MEMORY_LIMIT=-1 composer install --ansi --prefer-dist --no-interaction --no-progress'
      }
    }
    stage('tests:unit') {
      steps {
        sh 'SYMFONY_DEPRECATIONS_HELPER=disabled php -d date.timezone=UTC ./vendor/bin/phpunit -c tests/Unit/phpunit.xml'
      }
    }
    stage('build') {
      steps {
        sh 'docker-compose build'
      }
    }
    stage('launch') {
      steps {
        sh 'docker-compose up -d'
      }
    }
    stage('tests:functional') {
      steps {
        sh 'python3 -m xmlrunner discover tests/Functional -o reports'
      }
      post {
        always {
          junit 'reports/*.xml'
        }
      }
    }
  }

   post {
      always {
         sh "docker-compose down || true"
      }
   }
  environment {
    SRV_QA = 'http://127.0.0.1/prestashop/admin338j8tqkk'
  }
}
