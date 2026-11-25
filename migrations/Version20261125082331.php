<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261125082331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE archive_audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(255) NOT NULL, entity_type VARCHAR(180) NOT NULL, entity_id INT NOT NULL, details JSON NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_B2C88249A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE archive_document (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, original_filename VARCHAR(255) NOT NULL, stored_filename VARCHAR(255) NOT NULL, storage_path VARCHAR(512) NOT NULL, mime_type VARCHAR(150) NOT NULL, file_extension VARCHAR(20) NOT NULL, file_size BIGINT NOT NULL, checksum_sha256 VARCHAR(64) NOT NULL, version INT NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, folder_id INT NOT NULL, uploaded_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_F506451A162CB942 (folder_id), INDEX IDX_F506451AA2B28FE8 (uploaded_by_id), INDEX IDX_F506451AC76F1F52 (deleted_by_id), INDEX IDX_F506451AB03A8386 (created_by_id), INDEX IDX_F506451A896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE archive_folder (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, slug VARCHAR(180) NOT NULL, is_system_folder TINYINT NOT NULL, restricted_to_admin TINYINT NOT NULL, description LONGTEXT DEFAULT NULL, display_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, archive_project_id INT NOT NULL, parent_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_11A71DD8D4FEC979 (archive_project_id), INDEX IDX_11A71DD8727ACA70 (parent_id), INDEX IDX_11A71DD8B03A8386 (created_by_id), INDEX IDX_11A71DD8896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE archive_permission (id INT AUTO_INCREMENT NOT NULL, permissions JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, archive_folder_id INT DEFAULT NULL, archive_project_id INT DEFAULT NULL, INDEX IDX_22DA444FA76ED395 (user_id), INDEX IDX_22DA444F5205FBFE (archive_folder_id), INDEX IDX_22DA444FD4FEC979 (archive_project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE archive_project (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, code VARCHAR(80) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, archive_year_id INT NOT NULL, linked_public_project_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_4293311149A8BBEC (linked_public_project_id), INDEX IDX_429331112DE11F58 (archive_year_id), INDEX IDX_42933111B03A8386 (created_by_id), INDEX IDX_42933111896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE archive_year (id INT AUTO_INCREMENT NOT NULL, year INT NOT NULL, description LONGTEXT DEFAULT NULL, is_locked TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8BA7A5ACBB827337 (year), INDEX IDX_8BA7A5ACB03A8386 (created_by_id), INDEX IDX_8BA7A5AC896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(180) NOT NULL, category VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, published_at DATETIME DEFAULT NULL, is_published TINYINT NOT NULL, attachment_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_23A0E66989D9B62 (slug), INDEX IDX_23A0E66B03A8386 (created_by_id), INDEX IDX_23A0E66896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE award (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, issuer VARCHAR(180) DEFAULT NULL, year INT NOT NULL, description LONGTEXT DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, sender_name VARCHAR(180) NOT NULL, sender_email VARCHAR(180) NOT NULL, subject VARCHAR(180) DEFAULT NULL, message LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entity_translation (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(180) NOT NULL, entity_id INT NOT NULL, locale VARCHAR(10) NOT NULL, field VARCHAR(180) NOT NULL, value LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hero_slide (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, subtitle VARCHAR(255) DEFAULT NULL, image_path VARCHAR(255) NOT NULL, cta_label VARCHAR(120) DEFAULT NULL, cta_url VARCHAR(255) DEFAULT NULL, display_order INT NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE intervention_domain (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(180) NOT NULL, summary LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, icon_or_image VARCHAR(255) DEFAULT NULL, display_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_4D093C2E989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organization_setting (id INT AUTO_INCREMENT NOT NULL, organization_name VARCHAR(180) NOT NULL, logo_path VARCHAR(255) DEFAULT NULL, primary_color VARCHAR(20) DEFAULT NULL, short_description LONGTEXT DEFAULT NULL, mission LONGTEXT DEFAULT NULL, vision LONGTEXT DEFAULT NULL, organization_values LONGTEXT DEFAULT NULL, history LONGTEXT DEFAULT NULL, objectives LONGTEXT DEFAULT NULL, strategic_plan LONGTEXT DEFAULT NULL, key_figures JSON NOT NULL, address VARCHAR(255) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(180) DEFAULT NULL, phone_secondary VARCHAR(180) DEFAULT NULL, gps_coordinates VARCHAR(255) DEFAULT NULL, social_links JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_52A630DBB03A8386 (created_by_id), INDEX IDX_52A630DB896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_view_stat (id INT AUTO_INCREMENT NOT NULL, route VARCHAR(180) NOT NULL, url_path VARCHAR(255) NOT NULL, visited_date DATE NOT NULL, hits_count INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE partner (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, type VARCHAR(255) NOT NULL, logo_path VARCHAR(255) DEFAULT NULL, website_url VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_visible_on_home TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(180) NOT NULL, context LONGTEXT DEFAULT NULL, objectives LONGTEXT DEFAULT NULL, geographic_zone VARCHAR(180) DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, status VARCHAR(255) NOT NULL, target_groups LONGTEXT DEFAULT NULL, main_activities LONGTEXT DEFAULT NULL, results_obtained LONGTEXT DEFAULT NULL, show_donor_publicly TINYINT NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_2FB3D0EE989D9B62 (slug), INDEX IDX_2FB3D0EEB03A8386 (created_by_id), INDEX IDX_2FB3D0EE896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project_partner (project_id INT NOT NULL, partner_id INT NOT NULL, INDEX IDX_A7353273166D1F9C (project_id), INDEX IDX_A73532739393F8FE (partner_id), PRIMARY KEY (project_id, partner_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project_media (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, type VARCHAR(20) NOT NULL, path VARCHAR(255) NOT NULL, caption LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, project_id INT DEFAULT NULL, INDEX IDX_7979A892166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE public_document (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, path VARCHAR(255) NOT NULL, extension VARCHAR(10) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, project_id INT DEFAULT NULL, INDEX IDX_FD5D8689166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE testimonial (id INT AUTO_INCREMENT NOT NULL, beneficiary_name VARCHAR(180) NOT NULL, role_or_context VARCHAR(180) DEFAULT NULL, story_text LONGTEXT DEFAULT NULL, photo_path VARCHAR(255) DEFAULT NULL, video_url VARCHAR(255) DEFAULT NULL, is_featured_on_home TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, project_id INT DEFAULT NULL, INDEX IDX_E6BDCDF7166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(180) NOT NULL, is_active TINYINT NOT NULL, last_login_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE archive_audit_log ADD CONSTRAINT FK_B2C88249A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_document ADD CONSTRAINT FK_F506451A162CB942 FOREIGN KEY (folder_id) REFERENCES archive_folder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE archive_document ADD CONSTRAINT FK_F506451AA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_document ADD CONSTRAINT FK_F506451AC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_document ADD CONSTRAINT FK_F506451AB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_document ADD CONSTRAINT FK_F506451A896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_folder ADD CONSTRAINT FK_11A71DD8D4FEC979 FOREIGN KEY (archive_project_id) REFERENCES archive_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE archive_folder ADD CONSTRAINT FK_11A71DD8727ACA70 FOREIGN KEY (parent_id) REFERENCES archive_folder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE archive_folder ADD CONSTRAINT FK_11A71DD8B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_folder ADD CONSTRAINT FK_11A71DD8896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_permission ADD CONSTRAINT FK_22DA444FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE archive_permission ADD CONSTRAINT FK_22DA444F5205FBFE FOREIGN KEY (archive_folder_id) REFERENCES archive_folder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE archive_permission ADD CONSTRAINT FK_22DA444FD4FEC979 FOREIGN KEY (archive_project_id) REFERENCES archive_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE archive_project ADD CONSTRAINT FK_429331112DE11F58 FOREIGN KEY (archive_year_id) REFERENCES archive_year (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE archive_project ADD CONSTRAINT FK_4293311149A8BBEC FOREIGN KEY (linked_public_project_id) REFERENCES project (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_project ADD CONSTRAINT FK_42933111B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_project ADD CONSTRAINT FK_42933111896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_year ADD CONSTRAINT FK_8BA7A5ACB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE archive_year ADD CONSTRAINT FK_8BA7A5AC896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE organization_setting ADD CONSTRAINT FK_52A630DBB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE organization_setting ADD CONSTRAINT FK_52A630DB896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE project_partner ADD CONSTRAINT FK_A7353273166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_partner ADD CONSTRAINT FK_A73532739393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_media ADD CONSTRAINT FK_7979A892166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE public_document ADD CONSTRAINT FK_FD5D8689166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE testimonial ADD CONSTRAINT FK_E6BDCDF7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE archive_audit_log DROP FOREIGN KEY FK_B2C88249A76ED395');
        $this->addSql('ALTER TABLE archive_document DROP FOREIGN KEY FK_F506451A162CB942');
        $this->addSql('ALTER TABLE archive_document DROP FOREIGN KEY FK_F506451AA2B28FE8');
        $this->addSql('ALTER TABLE archive_document DROP FOREIGN KEY FK_F506451AC76F1F52');
        $this->addSql('ALTER TABLE archive_document DROP FOREIGN KEY FK_F506451AB03A8386');
        $this->addSql('ALTER TABLE archive_document DROP FOREIGN KEY FK_F506451A896DBBDE');
        $this->addSql('ALTER TABLE archive_folder DROP FOREIGN KEY FK_11A71DD8D4FEC979');
        $this->addSql('ALTER TABLE archive_folder DROP FOREIGN KEY FK_11A71DD8727ACA70');
        $this->addSql('ALTER TABLE archive_folder DROP FOREIGN KEY FK_11A71DD8B03A8386');
        $this->addSql('ALTER TABLE archive_folder DROP FOREIGN KEY FK_11A71DD8896DBBDE');
        $this->addSql('ALTER TABLE archive_permission DROP FOREIGN KEY FK_22DA444FA76ED395');
        $this->addSql('ALTER TABLE archive_permission DROP FOREIGN KEY FK_22DA444F5205FBFE');
        $this->addSql('ALTER TABLE archive_permission DROP FOREIGN KEY FK_22DA444FD4FEC979');
        $this->addSql('ALTER TABLE archive_project DROP FOREIGN KEY FK_429331112DE11F58');
        $this->addSql('ALTER TABLE archive_project DROP FOREIGN KEY FK_4293311149A8BBEC');
        $this->addSql('ALTER TABLE archive_project DROP FOREIGN KEY FK_42933111B03A8386');
        $this->addSql('ALTER TABLE archive_project DROP FOREIGN KEY FK_42933111896DBBDE');
        $this->addSql('ALTER TABLE archive_year DROP FOREIGN KEY FK_8BA7A5ACB03A8386');
        $this->addSql('ALTER TABLE archive_year DROP FOREIGN KEY FK_8BA7A5AC896DBBDE');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66B03A8386');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66896DBBDE');
        $this->addSql('ALTER TABLE organization_setting DROP FOREIGN KEY FK_52A630DBB03A8386');
        $this->addSql('ALTER TABLE organization_setting DROP FOREIGN KEY FK_52A630DB896DBBDE');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEB03A8386');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE896DBBDE');
        $this->addSql('ALTER TABLE project_partner DROP FOREIGN KEY FK_A7353273166D1F9C');
        $this->addSql('ALTER TABLE project_partner DROP FOREIGN KEY FK_A73532739393F8FE');
        $this->addSql('ALTER TABLE project_media DROP FOREIGN KEY FK_7979A892166D1F9C');
        $this->addSql('ALTER TABLE public_document DROP FOREIGN KEY FK_FD5D8689166D1F9C');
        $this->addSql('ALTER TABLE testimonial DROP FOREIGN KEY FK_E6BDCDF7166D1F9C');
        $this->addSql('DROP TABLE archive_audit_log');
        $this->addSql('DROP TABLE archive_document');
        $this->addSql('DROP TABLE archive_folder');
        $this->addSql('DROP TABLE archive_permission');
        $this->addSql('DROP TABLE archive_project');
        $this->addSql('DROP TABLE archive_year');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE award');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE entity_translation');
        $this->addSql('DROP TABLE hero_slide');
        $this->addSql('DROP TABLE intervention_domain');
        $this->addSql('DROP TABLE organization_setting');
        $this->addSql('DROP TABLE page_view_stat');
        $this->addSql('DROP TABLE partner');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_partner');
        $this->addSql('DROP TABLE project_media');
        $this->addSql('DROP TABLE public_document');
        $this->addSql('DROP TABLE testimonial');
        $this->addSql('DROP TABLE `user`');
    }
}
