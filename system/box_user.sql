/*
Navicat MySQL Data Transfer

Source Server         : 本地
Source Server Version : 50714
Source Host           : 127.0.0.1:3306
Source Database       : print

Target Server Type    : MYSQL
Target Server Version : 50714
File Encoding         : 65001

Date: 2018-09-21 11:48:40
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for box_user
-- ----------------------------
DROP TABLE IF EXISTS `box_user`;
CREATE TABLE `box_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `meccode` varchar(255) DEFAULT NULL COMMENT '摩尔城店铺登录编号',
  `token` varchar(255) DEFAULT NULL COMMENT '登录token',
  `uuid` varchar(50) NOT NULL COMMENT '打印设备uuid',
  `shopid` varchar(255) DEFAULT NULL COMMENT '酷猫商城店铺id',
  `shopcode` varchar(255) DEFAULT NULL COMMENT '酷猫商城店铺编号',
  `posid` varchar(255) DEFAULT NULL COMMENT 'pos机号',
  `devshopid` varchar(255) DEFAULT NULL COMMENT '酷猫商城店铺外部编号',
  `shop_name` varchar(255) DEFAULT NULL COMMENT '店铺名称',
  `box_stasus` tinyint(3) DEFAULT '0' COMMENT '盒子状态1在线0不在线',
  `printer_status` tinyint(3) unsigned DEFAULT '0' COMMENT '打印机状态1在线',
  `speed` int(11) DEFAULT '0' COMMENT '打印速度配置',
  PRIMARY KEY (`id`),
  KEY `idx_uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='盒子信息';

-- ----------------------------
-- Records of box_user
-- ----------------------------
INSERT INTO `box_user` VALUES ('1', null, null, '1d003a000c51363239393738', null, null, null, null, 'balabala(并口)', '0', '0', '2');
INSERT INTO `box_user` VALUES ('2', 'L12', null, '1f0043000c51363239393738', '1016897', 'L1049001', null, null, 'Mixblu(usb口)', '0', '0', '2');
