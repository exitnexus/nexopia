#!/bin/bash

############### Print Functions    ###############

# Print $1 in colour mentioned

print_in_red()
{
	echo -e "\033[31m"$1"\033[0m"	
}
print_in_yellow()
{
	echo -e "\033[33m"$1"\033[0m"	
}
print_in_green()
{
	echo -e "\033[32m"$1"\033[0m"	
}
print_in_blue()
{
	echo -e "\033[34m"$1"\033[0m"	
}

############### Check Their Launch ###############

# did they run it properly?
if [ -z "$1" ]
then
	print_in_red "usage: configure [PHP install directory]"
	exit
fi

install_path=$1

############### Script Starts Here ###############

# Get PHP source and store in $1 if no $1 then current dir
download_php() # to
{
	if [ -z "$1" ]
	then
		print_in_red "error exiting"
		exit
	fi

	print_in_green "Downloading PHP source to \""$1"\""
	cd $1
	wget -c "http://ca.php.net/get/php-5.2.4.tar.gz/from/this/mirror"
}

compile_php() # to from
{
	if [ -z "$1" ]
	then
		print_in_red "error exiting"
		exit
	fi
	if [ -z "$2" ]
	then
		print_in_red "error exiting"
		exit
	fi
	
	to=$1
	from=$2
	
	print_in_green "Compiling PHP to \""$to"\" from \""$from"\""
	cd $2
	print_in_blue "Extracting tar file..."
	tar -xzf php-5.2.4.tar.gz
	cd "php-5.2.4"
	print_in_blue "Running configure script..."
	env CC=gcc ./configure --enable-embed --with-mysql --with-gd --with-jpeg --with-zlib --with-curl --with-mcrypt --prefix=$to && print_in_blue "Doing Compile..." && make && make install
	
}

generate_path_setup_file()
{
	cd $1
	cd ../../..
	print_in_green "Generating php path setup file"
	echo -e "#!/bin/bash\nexport LD_LIBRARY_PATH="$install_path"/lib/\n" > "rap_path_setup"
	chmod +x rap_path_setup
	print_in_green "Usage:"
	print_in_blue ". ./rap_path_setup"
	print_in_blue "This script needs to be run before you launch your server to tell ruby where the php libs are."
	print_in_blue "(On a normal install it shouldn't be needed but we put php in a nonstandard location for safety reasons.)"
}

current_dir=`pwd`
download_php "/tmp"
mkdir -p $install_path
compile_php $install_path "/tmp"
generate_path_setup_file $current_dir
