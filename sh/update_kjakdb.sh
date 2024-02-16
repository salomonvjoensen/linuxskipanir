#!/bin/bash

DATABASE='kjakdb'
MYSQL_ROOT_USER='kjak_user'
MYSQL_ROOT_PASSWORD='kjak2kjak'
ANON_USER='anon'
ANON_PASSWORD='anonbrúkari'

# Function to check if user exists and create if not
ensure_user_exists() {
  local user=$1
  local password=$2

  echo "Checking if user $user exists..."
  if ! mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1 FROM mysql.user WHERE user = '$user'" | grep -q 1; then
    echo "User $user does not exist, creating..."
    mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -e "CREATE USER '$user'@'localhost' IDENTIFIED BY '$password';"
  else
    echo "User $user already exists."
  fi
}

# Grant permissions to user
grant_permissions() {
  local user=$1
  local database=$2
  local table=$3
  local privileges=$4

  echo "Granting $privileges privileges to $user on $database..."
  mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -e "GRANT $privileges ON $database.$table TO '$user'@'localhost';"
  mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"
}

# Function to check and add primary keys and indexes
add_primary_key_and_index() {
  local table=$1
  local primary_key=$2
  local index_columns=$3

  echo "Checking primary key for table $table..."
  if ! mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -D "$DATABASE" -sse "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY';" | grep -q 'PRIMARY'; then
    echo "Adding primary key ($primary_key) to $table..."
    mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -D "$DATABASE" -e "ALTER TABLE $table ADD PRIMARY KEY ($primary_key);"
  else
    echo "Primary key ($primary_key) already exists in $table."
  fi

  if [[ -n "$index_columns" ]]; then
    for index_column in ${index_columns//,/ }; do
      echo "Checking index for column $index_column in $table..."
      if ! mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -D "$DATABASE" -sse "SHOW INDEX FROM $table WHERE Column_name = '$index_column';" | grep -q "$index_column"; then
        echo "Adding index for column $index_column in $table..."
        mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -D "$DATABASE" -e "ALTER TABLE $table ADD INDEX ($index_column);"
      else
        echo "Index for column $index_column already exists in $table."
      fi
    done
  fi
}

# Function to check and add foreign keys
add_foreign_key() {
  local table=$1
  local constraint_name=$2
  local foreign_key=$3
  local references=$4

  echo "Checking foreign key $constraint_name in table $table..."
  if ! mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -D "$DATABASE" -sse "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = '$DATABASE' AND TABLE_NAME = '$table' AND CONSTRAINT_NAME = '$constraint_name';" | grep -q "$constraint_name"; then
    echo "Adding foreign key $constraint_name to $table..."
    mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -D "$DATABASE" -e "ALTER TABLE $table ADD CONSTRAINT $constraint_name FOREIGN KEY ($foreign_key) REFERENCES $references;"
  else
    echo "Foreign key $constraint_name already exists in $table."
  fi
}

# Function to set AUTO_INCREMENT
set_auto_increment() {
  local table=$1
  local column=$2

  echo "Setting AUTO_INCREMENT for $column in $table..."
  mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASSWORD" -D "$DATABASE" -e "ALTER TABLE $table MODIFY $column int(11) NOT NULL AUTO_INCREMENT;"
}

# Ensure 'anon' user exists and grant permissions
ensure_user_exists "$ANON_USER" "$ANON_PASSWORD"
grant_permissions "$ANON_USER" "$DATABASE" "*" "SELECT"
grant_permissions "$ANON_USER" "$DATABASE" "kjak_thread" "INSERT, UPDATE"
grant_permissions "$ANON_USER" "$DATABASE" "kjak_post" "INSERT, UPDATE"

# Add primary keys, indexes, and set auto-increment
add_primary_key_and_index 'kjak_post' 'post_id' 'thread_id'
add_primary_key_and_index 'kjak_table' 'forum_id'
add_primary_key_and_index 'kjak_thread' 'thread_id' 'forum_id'

set_auto_increment 'kjak_post' 'post_id'
set_auto_increment 'kjak_table' 'forum_id'
set_auto_increment 'kjak_thread' 'thread_id'

# Add foreign keys
add_foreign_key 'kjak_post' 'kjak_post_ibfk_1' 'thread_id' 'kjak_thread(thread_id)'
add_foreign_key 'kjak_thread' 'kjak_thread_ibfk_1' 'forum_id' 'kjak_table(forum_id)'
