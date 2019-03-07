# @callhistory_bot
Call log and recordings for ACR and Telegram. For this to work you should use ACR Unchained version, since official Google Play apk do not allow call log permission anymore.

# Usage
You can clone this and use with your own bot or DB or whatever.

Also you can just add @callhistory_bot in Telegram and set it up to automatically receive ACR call recordings there, because Telegram has infinite storage space!

MySQL DB is used here to corellate your SECRET code (which you write down in ACR) and your chat_id. You and only you will have access yo your recordings, if secret+chat_id pair do not match nothing would work.

This instance I develop with free Heroku app and MySQL DB.
