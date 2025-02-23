FROM ubuntu:24.10

RUN apt-get update \
    && apt-get install apt-transport-https ca-certificates curl software-properties-common -y \
    && curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add - \
    && add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu focal stable" \
    && apt-cache policy docker-ce \
    && apt-get install -y docker-ce-cli php-cli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
