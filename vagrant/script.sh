#
# iptables off
#
/sbin/iptables -F
/sbin/service iptables stop
/sbin/chkconfig iptables off
#
# yum repository
#
rpm -ivh http://ftp.riken.jp/Linux/fedora/epel/6/i386/epel-release-6-8.noarch.rpm
rpm -ivh http://dl.iuscommunity.org/pub/ius/stable/CentOS/6/x86_64/ius-release-1.0-11.ius.centos6.noarch.rpm
#yum -y update
#
# ntp
#
yum -y install ntp
/sbin/service ntpd start
/sbin/chkconfig ntpd on
#
# php
#
rpm -ivh http://rpms.famillecollet.com/enterprise/remi-release-5.rpm
yum -y install --enablerepo=remi --enablerepo=remi-php55 php php-cli php-pdo php-mbstring php-mcrypt php-pecl-memcache php-mysql php-devel php-common php-pgsql php-pear php-gd php-xml php-pecl-xdebug php-pecl-apc
touch /var/log/php.log && chmod 666 /var/log/php.log
cp -a /vagrant/php.ini /etc/php.ini
#
# Apache
#
cp -a /vagrant/httpd.conf /etc/httpd/conf/
/sbin/service httpd restart
/sbin/chkconfig httpd on
#
# PostgreSQL
#
rpm -ivh http://yum.postgresql.org/9.3/redhat/rhel-6-x86_64/pgdg-centos93-9.3-1.noarch.rpm
yum -y install postgresql93-server
if [ ! -f /var/lib/pgsql/9.3/data/postgresql.conf ]; then
  su postgres -c '/usr/pgsql-9.3/bin/initdb --no-locale -D /var/lib/pgsql/9.3/data'
  if [ -f /vagrant/postgresql.conf ]; then
    cp -a /vagrant/postgresql.conf /var/lib/pgsql/9.3/data/
  fi
fi
/sbin/service postgresql-9.3 restart
/sbin/chkconfig postgresql-9.3 on

/usr/bin/createuser -d -A -S -U postgres vagrant
/usr/bin/createdb -Uvagrant -E UTF-8 -T template0 app
/usr/bin/createdb -Uvagrant -E UTF-8 -T template0 app_test
#
# phpPgAdmin
#
sudo yum -y install phpPgAdmin
cp -a /vagrant/phpPgAdmin.conf /etc/httpd/conf.d/
/sbin/service httpd restart
#
# Composer
#
if [ -f /share/composer.json ]; then
  cd /share && curl -s http://getcomposer.org/installer | php
  /usr/bin/php /share/composer.phar install
fi
cp -a /share/app/Config/database.php.default /share/app/Config/database.php
cp -a /share/app/Config/bootstrap.php.default /share/app/Config/bootstrap.php
cp -a /share/app/Config/email.php.default /share/app/Config/email.php

#
# MySQL
#
yum -y install http://repo.mysql.com/mysql-community-release-el6-4.noarch.rpm
yum -y install mysql-community-server
#cp -a /vagrant/my.conf /etc/my.conf
/sbin/service mysqld restart
/sbin/chkconfig mysqld on

mysql -u root -e "create database timecard_dev default charset utf8"
mysql -u root -e "create database timecard_test default charset utf8"
mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'timecard'@'localhost' IDENTIFIED BY 'secret' WITH GRANT OPTION;"

