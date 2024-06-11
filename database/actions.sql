-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 11 juin 2024 à 13:56
-- Version du serveur : 8.2.0
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `pharma_finder`
--

-- --------------------------------------------------------

--
-- Structure de la table `actions`
--

DROP TABLE IF EXISTS `actions`;
CREATE TABLE IF NOT EXISTS `actions` (
  `id_action` int NOT NULL AUTO_INCREMENT,
  `libelle_action` varchar(50) DEFAULT NULL,
  `description_action` varchar(200) DEFAULT NULL,
  `methode` varchar(20) NOT NULL DEFAULT 'get',
  `url_action` varchar(50) DEFAULT NULL,
  `level` int NOT NULL,
  `parent` int DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_menu` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_action`),
  UNIQUE KEY `actions_un` (`libelle_action`,`methode`,`url_action`),
  KEY `actions_actions_id_action_fk` (`parent`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `actions`
--

INSERT INTO `actions` (`id_action`, `libelle_action`, `description_action`, `methode`, `url_action`, `level`, `parent`, `icon`, `is_menu`) VALUES
(2, 'Ajouter une action', 'Action pour ajouter les actions', 'post', '/v1/admin/actions', 1, NULL, NULL, 0),
(3, 'Consulter les actions', '', 'get', '/v1/admin/actions', 1, NULL, NULL, 0),
(4, 'Modifier les actions', '', 'put', '/v1/admin/actions', 1, NULL, NULL, 0),
(5, 'Supprimer les actions', '', 'delete', '/v1/admin/actions', 1, NULL, NULL, 0),
(6, 'Ajouter un profil', '', 'post', '/v1/admin/profils', 1, 15, NULL, 0),
(7, 'Consulter les profils', '', 'get', '/v1/admin/profils', 1, 15, NULL, 0),
(8, 'Modifier un profil', '', 'put', '/v1/admin/profils', 1, 15, NULL, 0),
(9, 'Supprimer un profil', '', 'delete', '/v1/admin/profils', 1, 15, NULL, 0),
(10, 'Affecter des actions à un profil', '', 'post', '/v1/admin/profils/actions', 1, 15, NULL, 0),
(11, 'Consulter les actions affectées un profil', '', 'get', '/v1/admin/profils/actions', 1, 15, NULL, 0),
(12, 'Supprimer les actions affectées à un profil', '', 'delete', '/v1/admin/profils/actions', 1, 15, NULL, 0),
(13, 'Ajouter un utilisateur', '', 'post', '/v1/admin/users', 1, 15, NULL, 0),
(14, 'Modifier un utilisateur', '', 'put', '/v1/admin/users', 1, 15, NULL, 0),
(15, 'Gestion des utilisateurs', '', 'get', '/v1/admin/users', 1, NULL, 'fa-solid fa-users', 1),
(16, 'Supprimer un utilisateur', '', 'delete', '/v1/admin/users', 1, 15, NULL, 0),
(17, 'Changer le mot de passe d\\\'un utilisateur', '', 'post', '/v1/admin/users/changePassword', 1, 15, NULL, 0),
(18, 'Réinitialiser le mot de passe', '', 'post', '/v1/admin/users/resetPassword', 1, 15, NULL, 0),
(19, 'Changer état profil', '', 'put', '/v1/admin/profils/setStatus', 1, 15, NULL, 0),
(20, 'Consulter les actions  profil', '', 'get', '/v1/admin/profils/actions', 1, 15, NULL, 0),
(21, 'Ajouter une categorie', '', 'post', '/v1/admin/categories', 1, 27, NULL, 0),
(22, 'Modifier une categorie', '', 'put', '/v1/admin/categories', 1, 27, NULL, 0),
(23, 'Categories', '', 'get', '/v1/admin/categories', 1, 27, NULL, 0),
(24, 'Supprimer une categorie', '', 'delete', '/v1/admin/categories', 1, 27, NULL, 0),
(25, 'Ajouter un produit', '', 'post', '/v1/admin/produits', 1, 27, NULL, 0),
(26, 'Modifier un produit', '', 'put', '/v1/admin/produits', 1, 27, NULL, 0),
(27, 'Gestion des Produits', '', 'get', '/v1/admin/produits', 1, NULL, 'fa-solid fa-pills', 1),
(28, 'Supprimer un produit', '', 'delete', '/v1/admin/produits', 1, 27, NULL, 0),
(29, 'Ajouter une pharmacie', '', 'post', '/v1/admin/pharmacies', 1, 31, NULL, 0),
(30, 'Modifier une pharmacie', '', 'put', '/v1/admin/pharmacies', 1, 31, NULL, 0),
(31, 'Gestion des Pharmacies', '', 'get', '/v1/admin/pharmacies', 1, NULL, 'fa-solid fa-building', 1),
(32, 'Changer le statut de la paharmacie', '', 'post', '/v1/admin/pharmacies/setStatus', 1, 31, NULL, 0),
(33, 'Supprimer une paharmacie', '', 'delete', '/v1/admin/pharmacies', 1, 31, NULL, 0),
(39, 'Attribuer un produit à une pharmacie', NULL, 'post', '/v1/admin/pharmacies/produits', 1, 31, NULL, 0),
(40, 'Consulter les produits d\'une pharmacie', '', 'get', '/v1/admin/pharmacies/produits', 1, 31, NULL, 0),
(41, 'Consulter les produits d\'une seule pharmacie', '', 'get', '/v1/admin/pharmacies/produits', 1, 31, NULL, 0),
(42, 'Modifier un produit d\'une pharmacie ', '', 'put', '/v1/admin/pharmacies/produits', 1, 31, NULL, 0),
(43, 'Supprimer un produit d\'une pharmacie', '', 'delete', '/v1/admin/pharmacies/produits', 1, 31, NULL, 0),
(44, 'Dashboard', 'Visualisation des données pertinentes', 'get', '/v1/admin/dashboard', 1, NULL, 'fa-solid fa-house', 0),
(45, 'Consulter la liste des groupes de garde', NULL, 'get', '/v1/admin/groupes_gardes', 1, 31, NULL, 0),
(46, 'Ajouter un groupe de garde', NULL, 'post', '/v1/admin/groupes_gardes', 1, 31, NULL, 0),
(47, 'Modifier un groupe de garde', NULL, 'put', '/v1/admin/groupes_gardes', 1, 31, NULL, 0),
(48, 'Changer le statut du groupe de garde', NULL, 'put', '/v1/admin/groupes_gardes/setStatus', 1, 31, NULL, 0),
(49, 'Supprimer un groupe de garde', NULL, 'delete', '/v1/admin/groupes_gardes', 1, 31, NULL, 0),
(50, 'Consulter la liste des pharmacies de garde', NULL, 'get', '/v1/admin/groupes_gardes/pharmacies', 1, 31, NULL, 0),
(51, 'Ajouter une pharmacie à un groupe de garde', NULL, 'post', '/v1/admin/groupes_gardes/pharmacies', 1, 31, NULL, 0),
(52, 'Rétirer une pharmacie d\'un groupe de garde', NULL, 'delete', '/v1/admin/groupes_gardes/pharmacies', 1, 31, '<null>', 0),
(53, 'Changer le statut d\'un utilisateur', 'Activer / Désactiver un utilisateur', 'put', '/v1/admin/users/setStatus', 1, 15, NULL, 0),
(54, 'Consulter  l\'administrateur d\'une pharmacie', NULL, 'get', '/v1/admin/pharmacies/admin', 1, 31, NULL, 0),
(55, 'Créer l\'administrateur d\'une pharmacie', NULL, 'post', '/v1/admin/pharmacies/admin', 1, 31, NULL, 0),
(56, 'Modifier l\'administrateur d\'une pharmacie', NULL, 'put', '/v1/admin/pharmacies/admin', 1, 31, NULL, 0),
(57, 'Gestion des utilisateurs', '', 'get', '/v1/users', 2, NULL, 'fa-solid fa-users', 1),
(58, 'Ajouter un utilisateur', '', 'post', '/v1/users', 2, 57, NULL, 0),
(59, 'Modifier un utilisateur', '', 'put', '/v1/users', 2, 57, NULL, 0),
(60, 'Supprimer un utilisateur', '', 'delete', '/v1/users', 2, 57, NULL, 0),
(61, 'Changer le mot de passe d\\\'un utilisateur', '', 'post', '/v1/users/changePassword', 2, 57, NULL, 0),
(62, 'Réinitialiser le mot de passe', '', 'post', '/v1/users/resetPassword', 2, 57, NULL, 0),
(63, 'Consulter les profils', '', 'get', '/v1/profils', 2, 57, NULL, 0),
(64, 'Ajouter un profil', '', 'post', '/v1/profils', 2, 57, NULL, 0),
(65, 'Modifier un profil', '', 'put', '/v1/profils', 2, 57, NULL, 0),
(66, 'Supprimer un profil', '', 'delete', '/v1/profils', 2, 57, NULL, 0),
(67, 'Affecter un profil à des actions', '', 'post', '/v1/profils/actions', 2, 57, NULL, 0),
(68, 'Consulter les actions affectées un profil', '', 'get', '/v1/profils/actions', 2, 57, NULL, 0),
(69, 'Supprimer les actions affectées à un profil', '', 'delete', '/v1/profils/actions', 2, 57, NULL, 0);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `actions`
--
ALTER TABLE `actions`
  ADD CONSTRAINT `actions_actions_id_action_fk` FOREIGN KEY (`parent`) REFERENCES `actions` (`id_action`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
