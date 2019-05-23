#!/usr/bin/env sh

echo "Starting exporter and tweaker(batch) processes..."

TEMP_LOG_FILE=/tmp/log.log
rm -f ${TEMP_LOG_FILE}

# Start the tweaker for batch updates.
python /usr/src/tweaker/tweak_web_auto.py >> ${TEMP_LOG_FILE} 2>&1 &

# Start the exporter, wait for file changes.
python /usr/src/app/export_web_auto.py >> ${TEMP_LOG_FILE} 2>&1 &

tail -f ${TEMP_LOG_FILE}
