# Check if argument is set
if [ -z "$1" ]
then
  echo "No argument supplied"
  exit 1
fi

# Check if argument is "production" or "dev"
if [ $1 != "production" ] && [ $1 != "dev" ]
then
  echo "Argument must be 'production' or 'dev'"
  exit 1
fi

# Check if argument is "production" and version number is set
if [ $1 == "production" ] && [ -z "$2" ]
then
  echo "No version number supplied"
  exit 1
fi

# Create build folder, overwrite if exists
rm -rf build
mkdir build

# Copy all files in this folder to build folder, except for build folder
cp -r assets build
cp -r includes build
cp -r vendor build
cp -r wp-bankid.php build
cp -r index.php build

# If argument is "production", add license file and update version number
if [ $1 == "production" ]
then
  cp -r LICENSE.md build/LICENSE.md
  sed -i 's/Version: .*/Version: '$2'/g' build/wp-bankid.php
  sed -i "s/define( 'WP_BANKID_VERSION', '.*' );/define( 'WP_BANKID_VERSION', '$2' );/g" build/wp-bankid.php
fi

# If argument is "dev", change plugin name and change license file
if [ $1 == "dev" ]
then
  sed -i 's/Plugin Name: WP BankID by Webbstart/Plugin Name: WP BankID DEV/g' build/wp-bankid.php
  cp -r _dev/LICENSE.md build/LICENSE.md
fi