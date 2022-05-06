#!/bin/bash
db=$1
tempPath=$2
lines=$(sudo mysql --skip-column-names -e"SHOW FULL tables FROM ${db}")
while read -r table type; do
  if [[ $type == "VIEW" ]]; then
    DROP_TABLES_STRING+=" drop view ${db}.${table};"
  else
    DROP_TABLES_STRING+=" drop table ${db}.${table};"
  fi
done <<<"$lines"

declare dropPath="${tempPath}${db}.drop.sql"
echo "${DROP_TABLES_STRING}" >>${dropPath}
sudo mysql ${db} <${dropPath}
rm -f ${dropPath}
