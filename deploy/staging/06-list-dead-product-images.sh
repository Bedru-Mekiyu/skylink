S=/home/skylinkictcom/bedru.skylinkict.com
envget() { local v; v="$(grep -E "^[[:space:]]*$1=" "$S/.env" | tail -n1)"; v="${v#*=}"; printf '%s' "${v%$'\r'}"; }
# READ-ONLY query on the staging database (a copy of live): products whose image is a hot-linked external URL
MYSQL_PWD="$(envget DB_PASSWORD)" mysql -h 127.0.0.1 -u "$(envget DB_USERNAME)" "$(envget DB_DATABASE)" -e "select id, name, category, slug, left(image, 60) as image from products where image like 'http%'" 2>&1
