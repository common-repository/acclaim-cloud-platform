===  Acclaim Cloud Platform ===
Contributors: acclaimconsultinggroup
Donate link: https://acclaimconsulting.com/?page_id=951
Tags: cloud platform, private cloud, web services, web instances, cloud instances
Requires at least: 4.7 
Tested on: 6.1.1
Tested up to: 6.1.1 
Requires PHP: 5.2
Stable tag: 1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Acclaim Cloud Platform allows anyone with modest Wordpress and Unix skills to run their own cloud based platform for internal or commercial use to others. 
The core vision of the plugin is to make cloud computing accessible to all small and medium-sized firms, and democratize cloud computing for everyone.

Server instances are private and are not accessible publicly except via the bundled VPN solution. 

ACP Pro Addon has additional features:
-Removes server instance limits
-Architecture and Design Guidance and Support
 
== Installation ==

This is an enterprise plugin. After you install the plugin, additional configuration is required before it becomes operational:

1. Following <a href="https://acclaimconsulting.com/?page_id=873"> step by step instructions </a> available on plugin site, download and deploy a fully configured Virtualization Server in your environment.
The server is provided as a bootable Ubuntu ISO image. This step requires Unix deployment skills.

Note: 
Using this plugin requires some Unix skills to complete deployment. In general, this requires a Unix Administrator skill. Please don't attempt unless you have some Unix background.

Note: 
The Virtualization server needs Internet access to download opensource components.

Note:
The plugin does not any services hosted by the plugin developer. Once you deploy the Virtualization Server in your environment, 
you will have complete control, just like you would any of your internal servers. And just like any of your open source servers, it will need access to publicly available repositories to get server updates. 

== Frequently Asked Questions ==

See <a href="https://acclaimconsulting.com/?page_id=873"> FAQ </a>

== Screenshots ==

[https://acclaimconsulting.com/wp-content/uploads/2020/02/screenshot-1.jpg  Acclaim Cloud Platform Dashboard 1]
[https://acclaimconsulting.com/wp-content/uploads/2020/02/screenshot-2.jpg  Acclaim Cloud Platform Dashboard 2]
[https://acclaimconsulting.com/wp-content/uploads/2020/02/screenshot-3.jpg  Acclaim Cloud Platform Dashboard 3]
[https://acclaimconsulting.com/wp-content/uploads/2020/02/screenshot-4.jpg  Acclaim Cloud Platform Dashboard 4]

== Upgrade Notice == 

See <a href="https://acclaimconsulting.com/?page_id=1081"> Upgrade Information </a>

== Changelog ==

= 1.0 =
Initial Version

= 1.1 =
Acclaim Cloud Platform 1.1 (February 24, 2020)

-Fixed routing issues with VPN accessing Private Subnet
-Fixed issue where plugin was redirecting admin logins to plugin specific page
-Added support for ACP Pro Addon
-Hardened and shrunk accompanying ACP Image Size
-Overall code optimization 

= 1.2 =
Acclaim Cloud Platform 1.2 (March 23, 2020)

-New, easier, simpler, shorter deployment model - see updated <a href="https://acclaimconsulting.com/?page_id=873"> instructions </a> 
-Under-the-covers improvements to ACP Session Management
-Added ability to select Guest Network IP as a plugin option
-Fixed sudo issue with guest admin user provisioning
-Spruced up the ACP Image UI using our public web site theme, layout, look and feel and other elements

= 1.3 = (April 6, 2020)
-Tested on Wordpress 5.4
-Made deployment easier by adding default working values for ACP Plugin settings. Now, once you spin up the image, you will be ready to go.
-Added clustering hooks (on the ACP Image) in prep for future clustered version

= 1.4 = (July 31, 2020)
-Added full unlimited horizontal clustering architecture
-Add as many horizontal Hosts to as you add guests
-As Hosts are added, they are automatically detected and added to the ACP Cluster
-Full database and web tier clustering using Percona and Unison

= 1.5 = (August 18, 2020)
- Wordpress 5.5 compliance release

= 1.6 = (May 28, 2021)
-Wordpress 5.7 compliance release
-Update Host OS to Ubuntu 20.04
-Added Windows Guest Support
-Enhanced Clustering

= 1.7 = (Nov 18, 2022)
-Wordpress 6.1 compliance release
-Update Host OS to Ubuntu 22.04
-Reliability and stability fixes
