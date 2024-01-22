<?php

namespace App\Services;

use App\Classes\Menu;
use App\Classes\MenuCategory;
use App\Classes\Submenu;

class MenuService
{
    // public static function menu() : array
    // {
    //     return [
    //         self::userMenu(),
    //         self::fileMenu(),
    //         self::departmentMenu(),
    //         self::settingMenu(),
    //     ];
    // }

    public static function menu() : array
    {
        return [
            self::masterData(),
            self::manajemenDokumen(),
            self::masterSetting(),
        ];
    }

    public static function visitorMenu() : array
    {
        return [
            new MenuCategory('MANAJEMEN DOKUMEN', ...[self::fileMenu(), self::suratMasuk(), self::suratKeluar(), self::peminjamanMenu()])
        ];
    }

    protected static function masterData()
    {
        return new MenuCategory('MASTER DATA', ...[self::userMenu(), self::departmentMenu(), self::companyMenu(), self::categoryMenu(), self::archiveMenu()]);
    }

    protected static function userMenu()
    {
        $user = new Submenu('user_access', 'users', 'fa fa-users', 'User');

        return new Menu('user_access', 'fa fa-users', 'User', true, ...[$user]);
    }

    protected static function departmentMenu()
    {
        $department = new Submenu('internal_department_access', 'internal-departments', 'fa fa-sitemap', 'Departemen Internal');

        return new Menu('internal_department_access', 'fa fa-sitemap', 'Departments', true, ...[$department]);
    }

    protected static function companyMenu()
    {
        $company = new Submenu('company_access', 'companies', 'fa fa-building', 'Perusahaan');
        $department = new Submenu('department_access', 'departments', 'fa fa-sitemap', 'Departemen');

        return new Menu('company_management_access', 'fa fa-building', 'Perusahaan', true, ...[$company, $department]);
    }

    protected static function categoryMenu()
    {
        $category = new Submenu('category_access', 'categories', 'fa fa-list', 'Kategori');

        return new Menu('category_access', 'fa fa-list', 'Kategori', true, ...[$category]);
    }

    protected static function archiveMenu()
    {
        $archiveType = new Submenu('archive_type_access', 'archive-types', 'fa fa-clipboard-list', 'Tipe Arsip');
        $archive = new Submenu('archive_access', 'archives', 'fa fa-clipboard-list', 'Arsip');

        return new Menu('archive_management_access', 'fa fa-clipboard-list', 'Arsip', true, ...[$archiveType, $archive]);
    }

    protected static function manajemenDokumen()
    {
        return new MenuCategory('MANAJEMEN DOKUMEN', ...[self::fileMenu(), self::suratMasuk(), self::suratKeluar(), self::peminjamanMenu(), self::approvalMenu()]);
    }

    protected static function fileMenu()
    {
        $file = new Submenu('file_access', 'files', 'fa fa-file', 'Files');

        return new Menu('file_access', 'fa fa-folder', 'Files', true, ...[$file]);
    }

    protected static function suratMasuk()
    {
        $suratMasuk = new Submenu('surat_masuk_access', 'mails/in', 'fa fa-download', 'Surat Masuk');

        return new Menu('surat_masuk_access', 'fa fa-download', 'Surat Masuk', true, ...[$suratMasuk]);
    }

    protected static function suratKeluar()
    {
        $suratKeluar = new Submenu('surat_keluar_access', 'mails/out', 'fa fa-upload', 'Surat Keluar');

        return new Menu('surat_keluar_access', 'fa fa-upload', 'Surat Keluar', true, ...[$suratKeluar]);
    }

    protected static function peminjamanMenu()
    {
        $peminjaman = new Submenu('file_borrow_access', 'file-borrows', 'fa fa-handshake', 'Peminjaman');

        return new Menu('file_borrow_access', 'fa fa-handshake', 'Peminjaman', true, ...[$peminjaman]);
    }

    protected static function approvalMenu()
    {
        $approval = new Submenu('approval_access', 'approvals', 'fa fa-check-double', 'Approval');

        return new Menu('approval_access', 'fa fa-check-double', 'Approval', true, ...[$approval]);
    }

    protected static function masterSetting()
    {
        return new MenuCategory('SETTING', ...[self::settingMenu(), self::roleMenu()]);
    }

    protected static function settingMenu()
    {
        $setting = new Submenu('setting_access', 'settings', 'fa fa-cog', 'Setting');

        return new Menu('setting_access', 'fa fa-cog', 'setting', true, ...[$setting]);
    }

    protected static function roleMenu()
    {
        $role = new Submenu('role_access', 'roles', 'fa fa-universal-access', 'Role & Hak Akses');

        return new Menu('role_access', 'fa fa-universal-access', 'setting', true, ...[$role]);
    }
}
