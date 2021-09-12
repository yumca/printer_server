/*
Navicat MySQL Data Transfer

Source Server         : 本地
Source Server Version : 50714
Source Host           : 127.0.0.1:3306
Source Database       : print

Target Server Type    : MYSQL
Target Server Version : 50714
File Encoding         : 65001

Date: 2017-11-30 14:07:31
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for print
-- ----------------------------
DROP TABLE IF EXISTS `print`;
CREATE TABLE `print` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(50) DEFAULT NULL,
  `guid` varchar(50) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `orgdata` text,
  `is_parse` tinyint(2) DEFAULT '0' COMMENT '0为解析  1有图片  2有文字',
  `CmdCode` varchar(20) DEFAULT NULL,
  `PackeId` varchar(20) DEFAULT NULL,
  `DataPackBuf` longtext,
  `DataPackeLen_oct` varchar(10) DEFAULT NULL,
  `DataPackeTrueLen` varchar(10) DEFAULT NULL,
  `DataPackeLen_left` varchar(255) DEFAULT NULL,
  `mctime` varchar(50) DEFAULT NULL,
  `ctime` varchar(50) DEFAULT NULL,
  `createtime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `uptime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
