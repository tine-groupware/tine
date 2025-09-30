#!/usr/bin/python
##########################################
#
# (c) copyright Metaways Infosystems GmbH 2025
# authors: Reinhard Vicinus <r.vicinus@metaways.de>
#
#  Python script using 'requests', 'json' and 'http.client' to connect to tine and sync Addressbook
#  contacts from tine to Sipgate. The tine contact id is persisted in the address data
#  ("extendedAddress") in Sipgate.
#
##########################################

import requests
from requests.auth import HTTPBasicAuth
import json
from http.client import HTTPConnection

HTTPConnection.debuglevel = 0

SIPGATE_URL = 'https://api.sipgate.com/v2'
SIPGATE_AUTH = HTTPBasicAuth('token', 'xyz')
TINE_AUTH = {
  "username": 'abc',
  "password": 'def'
}
TINE_URL = 'https://my.tine.org'

def tine_to_sipgate_contact(tine_contact):
  sipgate_contact = {
    'name': tine_contact["n_fn"],
    'family': tine_contact["n_family"],
    'given': tine_contact["n_given"],
    'scope': 'SHARED',
    'numbers': [],
    'emails': [],
    'picture': None,
    'addresses': [{
      "extendedAddress": tine_contact["id"],
      "country": "TINE_ID",
    }],
  }
  if 'tel_work_normalized' in tine_contact:
    sipgate_contact['numbers'].append(
      {
        "number": tine_contact["tel_work_normalized"],
        "type": ["work"]
      }
    )
  if 'tel_cell_normalized' in tine_contact:
    sipgate_contact['numbers'].append(
      {
        "number": tine_contact["tel_cell_normalized"],
        "type": ["cell"]
      }
    )
  if 'tel_fax_normalized' in tine_contact:
    sipgate_contact['numbers'].append(
      {
        "number": tine_contact["tel_fax_normalized"],
        "type": ["work", 'fax']
      }
    )
  if 'org_name' in tine_contact:
    sipgate_contact.update({'organization':  [[tine_contact["org_name"]]]})
  if 'email' in tine_contact:
    sipgate_contact['emails'].append(
      {
        "email": tine_contact["email"],
        "type": ["WORK"],
      }
    )
  return sipgate_contact
  
def create_sipgate_contact(tine_contact):
  payload = tine_to_sipgate_contact(tine_contact)
  response = requests.post(f'{SIPGATE_URL}/contacts', data=json.dumps(payload), headers={'content-type': 'application/json'}, auth=SIPGATE_AUTH)
  if response.ok:
    print(f"Successfully created {tine_contact['id']} {tine_contact['n_fn']}")
  else:
    print(f"Failure creating {tine_contact['id']} {tine_contact['n_fn']}")

def update_sipgate_contact(sipgate_id, tine_contact):
  payload = tine_to_sipgate_contact(tine_contact)
  payload.update({'id': sipgate_id })
  response = requests.put(f'{SIPGATE_URL}/contacts/{sipgate_id}', data=json.dumps(payload), headers={'content-type': 'application/json'}, auth=SIPGATE_AUTH)
  if response.ok:
    print(f"Successfully updated {tine_contact['id']} {tine_contact['n_fn']}")
  else:
    print(f"Failure updating {tine_contact['id']} {tine_contact['n_fn']}")

def delete_sipgate_contact(sipgate_id, tine_contact):
  response = requests.delete(f'{SIPGATE_URL}/contacts/{sipgate_id}', auth=SIPGATE_AUTH)
  if response.ok:
    print(f"Successfully deleted {tine_contact['id']} {tine_contact['n_fn']}")
  else:
    print(f"Failure deleting {tine_contact['id']} {tine_contact['n_fn']}")

def get_sipgate_contacts():
  response = requests.get(f'{SIPGATE_URL}/contacts', auth=SIPGATE_AUTH).json()
  sipgate_contacts = filter(lambda item: any(address.get('country') == 'TINE_ID' for address in item['addresses']), response['items'])
  sipgate_contacts_by_tine_id = dict((item['addresses'][0]['extendedAddress'], item) for item in sipgate_contacts)
  return sipgate_contacts_by_tine_id

def get_tine_contacts():
  url = TINE_URL
  headers = {
    'content-type': 'application/json'
  }

  payload = {
      "method": "Tinebase.login",
      "params": TINE_AUTH,
      "jsonrpc": "2.0",
      "id": 0,
  }
  response = requests.post(url, data=json.dumps(payload), headers=headers).json()

  #print(response)
  assert response["result"]["success"] == True
  assert response["jsonrpc"] == "2.0"
  assert int(response["id"]) == 0

  headers = {
    'content-type': 'application/json',
    'Cookie': f'TINE20SESSID={response["result"]["sessionId"]}',
    'X-Tine20-JsonKey': response["result"]['jsonKey']
  }

  #print(headers)
  payload = {
      "method": "Addressbook.searchContacts",
      "params": {
        "filter": []
      },
      "jsonrpc": "2.0",
      "id": 1,
  }
  response = requests.post(url, data=json.dumps(payload), headers=headers).json()
  #print(json.dumps(response))

  tine_contacts = dict((item['id'], item) for item in response['result']['results'])
  return tine_contacts

def transfer_tine_contact(tine_contact):
  if tine_contact.get('tel_work_normalized', None):
    return True
  if tine_contact.get('tel_cell_normalized', None):
    return True
  return False

def main():
  sipgate_contacts = get_sipgate_contacts()
  tine_contacts = get_tine_contacts()
  #print(json.dumps(tine_contacts))
  #exit()

  removed_keys = [i for i in sipgate_contacts.keys() if i not in tine_contacts.keys()]
  for key in removed_keys:
    delete_sipgate_contact(sipgate_contacts[key]['id'], {'id': key, 'n_fn': sipgate_contacts[key]['name']})


  for key in tine_contacts:
    if not transfer_tine_contact(tine_contacts[key]):
      if key in sipgate_contacts:
        delete_sipgate_contact(sipgate_contacts[key]['id'], tine_contacts[key])
      continue

    if key in sipgate_contacts:
      update_sipgate_contact(sipgate_contacts[key]['id'], tine_contacts[key])
    else:
      create_sipgate_contact(tine_contacts[key])

if __name__ == "__main__":
    main()
