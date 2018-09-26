FROM python:2.7-alpine3.6

MAINTAINER matthew
LABEL version="1.0.0"

WORKDIR /usr/src/app

RUN echo "@edge http://nl.alpinelinux.org/alpine/edge/main" >> /etc/apk/repositories && \
        apk update && \
        # apk add "libpq-dev"
        apk add build-base postgresql-dev

RUN pip install --no-cache-dir psycopg2 openpyxl pika

COPY uploader /usr/src/app/

# CMD ["python","-c","import time;time.sleep(10000)"]
CMD [ "python", "/usr/src/app/uploader.py" ]