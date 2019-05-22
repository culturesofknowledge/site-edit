#!/usr/bin/env sh

TEMP_LOG_FILE=/tmp/log.log
rm ${TEMP_LOG_FILE}

# Start the tweaker for batch updates.
python /usr/src/tweaker/tweak_web_auto.py &> ${TEMP_LOG_FILE} &

# Start the exporter, wait for file changes.
python /usr/src/app/export_web_auto.py &> ${TEMP_LOG_FILE} &

tail -f ${TEMP_LOG_FILE}
