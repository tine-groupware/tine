# /bin/sh

# ./console tine:cli -- '--method=Tinebase.createDemoData --username tine20admin --password tine20admin -- demodata=set set=crewscheduling.yml'

# eventTypes.json
curl $URL \
  -H 'Content-Type: application/json' \
  -H 'Cookie: TINE20SESSID='"$SESSIONID"'' \
  -H 'X-Requested-With: XMLHttpRequest' \
  -H 'X-Tine20-JsonKey: '"$JSONKEY"'' \
  -H 'X-Tine20-Request-Type: JSON' \
  --insecure \
  -d '{"jsonrpc":"2.0","method":"Calendar.searchEventTypes","params": [],"id":3}' \
  | jq > eventTypes.json

# schedulingRoles.json
curl $URL \
  -H 'Content-Type: application/json' \
  -H 'Cookie: TINE20SESSID='"$SESSIONID"'' \
  -H 'X-Requested-With: XMLHttpRequest' \
  -H 'X-Tine20-JsonKey: '"$JSONKEY"'' \
  -H 'X-Tine20-Request-Type: JSON' \
  --insecure \
  -d '{"jsonrpc":"2.0","method":"CrewScheduling.searchSchedulingRoles","params": [],"id":3}' \
  | jq > schedulingRoles.json


// attendee.json
// open crewScheduling
// xhr response from searchEvents of cs init process

event.json
// open crewScheduling
// xhr response from searchEvents of cs init process

lists.json
// xhr response from searchLists of cs init process
