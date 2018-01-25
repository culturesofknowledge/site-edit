FROM python:2.7-alpine3.7

MAINTAINER matthew
LABEL version="1.0.0"

WORKDIR /usr/src/app

RUN pip install --no-cache-dir psycopg2 openpyxl

COPY exporter /usr/src/app/

CMD ["python"]
# CMD [ "python", "./your-daemon-or-script.py" ]