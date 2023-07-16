# Check if argument is set
if [ -z "$1" ]
then
  echo "BUILD ERROR: No argument supplied"
  exit 1
fi

# Check if argument is "production" or "dev"
if [ $1 != "production" ] && [ $1 != "dev" ]
then
  echo "BUILD ERROR: Argument must be 'production' or 'dev'"
  exit 1
fi

# Update composer packages
echo "Updating composer packages (should also be made manually as often as possible)..."
composer update -o --no-progress > /dev/null 2>&1

# Check if argument is "production" and version number is set
if [ $1 == "production" ] && [ -z "$2" ]
then
  echo "BUILD ERROR: No version number supplied"
  exit 1
fi

# Create build folder, overwrite if exists
rm -rf build
mkdir build

# Copy all files in this folder to build folder, except for build folder
cp -r assets build
cp -r includes build
cp -r vendor build
cp -r mobile-bankid-integration.php build
cp -r index.php build

# If argument is "production", add license file, minimize js/css and update version number
if [ $1 == "production" ]
then
  echo "Building production version $2..."
  cp -r LICENSE.md build/LICENSE.md
  gsed -i 's/Version: .*/Version: '$2'/g' build/wp-bankid.php
  gsed -i "s/define( 'MOBILE_BANKID_INTEGRATION_VERSION', '.*' );/define( 'MOBILE_BANKID_INTEGRATION_VERSION', '$2' );/g" build/mobile-bankid-integration.php
  #Minimize CSS
  cp -r build/assets/css/setup.css build/assets/css/setup.full.css
  cleancss -o build/assets/css/setup.css build/assets/css/setup.css

  #Minimize JS
  cp -r build/assets/js/setup.js build/assets/js/setup.full.js
  uglifyjs build/assets/js/setup.js -o build/assets/js/setup.js
  cp -r build/assets/js/login.js build/assets/js/login.full.js
  uglifyjs build/assets/js/login.js -o build/assets/js/login.js
fi

# If argument is "dev", change plugin name and change license file
if [ $1 == "dev" ]
then
  echo "Building development version..."
  gsed -i 's/Plugin Name: Mobile BankID Integration/Plugin Name: Mobile BankID Integration DEV/g' build/mobile-bankid-integration.php
  cp -r _dev/LICENSE.md build/LICENSE.md
fi

# Zip contents of build folder
cd build
if [ $1 == "production" ]
then
  zip -r ../build-$2.zip . > /dev/null 2>&1
fi
if [ $1 == "dev" ]
then
  zip -r ../build-dev.zip . > /dev/null 2>&1
fi
cd ..

# Remove build folder
rm -rf build

# Done
echo "Done!"