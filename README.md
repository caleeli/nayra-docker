## Development
To run this project locally, you can use the following commands:

```
composer install
php start.php start
```

## Build docker
To build the docker image, you can use the following commands:

```
docker build --pull --rm -f "Dockerfile" -t nayra-docker:latest .
```

## Run docker
To run the docker image locally, you can use the following commands:

```
docker run -p 3000:3000 -it nayra-docker
```
