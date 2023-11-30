Mass-delete users with a given list of mail addresses or usernames
=

Use this script:

~~~bash
while IFS= read -r line; do
    # Check if the line is non-empty before processing
    if [ -n "$line" ]; then
		sudo -u www-data php /path/to/tine/tine20.php --username=admin --password=****** --method Admin.deleteAccount -- accountEmail=$line; done
    fi
done < "/mails.txt"
~~~

mails.txt can look like this (you can also make this work with usernames as deleteAccount supports both):

~~~
mail1@tine.net
mail2@tine.net
mail3@tine.net
~~~
