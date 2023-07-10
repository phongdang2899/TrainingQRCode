=== SETUP DOCKER ON WINDOWS ===

1. Install Linux kernel:
   https://shorturl.at/nDFSZ
2. Download and install docker: https://www.docker.com/products/docker-desktop
3. Install php 8.0: https://windows.php.net/download#php-8.0
4. Install Composer: https://getcomposer.org/download/

=== HOW TO RUN THIS PROJECT ===

1. run cmd: composer install
2. run cmd: cp .env.example .env
3. run cmd: php artisan key:generate
4. run cmd: npm i
5. run cmd: npm run dev
6. run cmd: cd ./docker
7. run cmd: docker-compose build
8. run cmd: docker-compose up
   or start docker container on Docker Desktop.
9. add a new cmd tab and run step by step these command:
    - docker exec -it qr_app bash
    - php artisan config:cache
    - php artisan migrate --seed
10. direct to: http://localhost:8383/
