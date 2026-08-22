# ShinseiFlow

**WordPress 申請審查與核准工作流程**

[English](README.md) | [日本語](README_ja.md) | [繁體中文](README_zh-Hant.md)

---

## 概要

ShinseiFlow 是一款開源 WordPress 外掛，用於建立申請表單、接收申請、審查內容、管理核准結果、寄送通知信，以及提供核准後的下載檔案。

它不只是收集表單資料，也能處理申請送出後的完整流程，包括審查、核准、不核准、追加確認、修正後重新送出，以及申請人查詢目前狀態與已提交內容。

## 適合哪些使用者

ShinseiFlow 適合希望直接在自己的 WordPress 網站內完成申請、審查、通知與結果管理，而不依賴外部 SaaS 申請管理平台的政府單位、學校、組織、企業與專案團隊。

## 常見應用情境

- 政府機關與地方單位的各類申請
- 補助與獎助金申請
- 獎學金申請
- 活動、講座與研討會報名
- 志工招募
- 競賽、公開徵件與作品申請
- 各類使用許可申請
- 內部申請與核准流程
- 僅限核准者下載的檔案提供
- 任何需要人工確認與審查的申請流程

## 不只是表單外掛

一般表單外掛主要負責接收輸入內容。

ShinseiFlow 則著重於申請送出之後的管理流程：

- 在 WordPress 後台檢視申請內容
- 管理核准與不核准結果
- 視需要要求申請人追加確認或補充資料
- 依照流程狀態寄送通知信
- 讓申請人查詢目前狀態與已提交內容
- 支援修正與重新送出
- 核准後提供下載檔案
- 保存申請歷程與狀態變更紀錄

## 主要功能

- 可自訂的申請表單
- 內建申請人姓名、Email 與電話號碼系統欄位
- 多種自訂欄位類型
- 同意事項欄位
- 依區段整理表單內容
- 多步驟申請流程
- 申請內容審查與核准管理
- 核准與不核准結果管理
- 追加確認流程
- 申請人前台狀態查詢
- 修正與重新送出
- 通知信模板
- 核准後下載檔案
- Cloudflare Turnstile 支援
- 內建防垃圾訊息與頻率限制機制
- 多語言申請表單、同意事項、前台標籤與系統訊息
- 可設定內容的 AI 輔助翻譯
- 顯示樣式自訂
- 隱私與資料保存設定
- 申請歷程與操作紀錄

## 系統需求

- WordPress 6.5 以上
- 已測試至 WordPress 7.1
- PHP 8.0 以上
- 授權：GPL-2.0-or-later

## 安裝方式

1. 將外掛資料夾上傳至 `/wp-content/plugins/`，或透過 WordPress 後台安裝。
2. 啟用 **ShinseiFlow – Application Review & Approval Workflow**。
3. 設定申請表單、通知信、安全性、多語言與前台頁面。
4. 將需要的 shortcode 放入對應頁面。

## Shortcodes

- `[tcarm_form]` — 申請表單
- `[tcarm_status]` — 申請狀態查詢
- `[tcarm_view]` — 申請內容檢視
- `[tcarm_edit]` — 修正與重新送出表單

## 多語言支援

ShinseiFlow 內建自己的多語言設定機制，可針對申請表單、前台標籤、系統訊息、同意事項及其他可設定內容建立多語言版本。

此外，外掛本身也已依照 WordPress 標準翻譯機制完成國際化（i18n）。

外掛套件不再直接內含 `.po`、`.mo` 等翻譯檔案。正式發布於 WordPress.org 後，WordPress 介面翻譯可透過 [translate.wordpress.org](https://translate.wordpress.org/) 由社群協作、管理與配送。

## 選用的外部服務

ShinseiFlow 可在管理員明確設定後，選擇性地與外部服務整合。

### Cloudflare Turnstile

可以啟用 Cloudflare Turnstile，為前台申請流程提供機器人防護。

此功能預設為停用狀態，只有在管理員完成必要設定並主動啟用後，才會載入並使用相關服務。

### AI 輔助翻譯

可針對可設定的表單及介面內容使用選用的 AI 輔助翻譯功能。

目前支援 OpenAI 與 Google Gemini。AI 翻譯只會由管理員主動操作觸發，不會自動將申請人提交的申請內容交由 AI 處理。

關於外部服務、傳送資料、服務條款及隱私權政策的完整資訊，請參閱外掛內的 `readme.txt`。

## 文件與支援

官方網站、使用文件、支援資訊與問題回報：

https://labs.tungchi.jp/shinseiflow/

## 專案狀態

- 目前版本：**0.4.3.48**
- 目前正在進行 WordPress.org Plugin Directory 上架審查
- 支援 WordPress 6.5 以上版本
- 已於 WordPress 7.1 完成測試
- 已完成 WordPress 國際化，可支援 WordPress.org 社群翻譯
- 支援申請表單、同意事項、前台標籤與系統訊息的多語言設定
- 提供選用的 Cloudflare Turnstile 整合
- 提供可設定內容的選用 AI 輔助翻譯

ShinseiFlow 目前仍持續開發中。在 WordPress.org 審查完成之前，GitHub 將作為最新開發版本與送審候選版本的原始碼來源。

## 開發理念

ShinseiFlow 以長期維護、操作體驗、隱私、安全性與 WordPress 相容性為主要設計原則。

開發過程使用 AI 作為輔助工具，但功能規劃、程式架構、測試、安全性檢查、操作體驗判斷與版本品質，仍由人工確認。

目標不是單純生成程式碼，而是打造穩定、可維護，並能實際應用在 WordPress 網站中的外掛。

## 支持專案

ShinseiFlow 是由個人獨立開發與維護的開源專案。

若此外掛對你或你的組織有所幫助，可以透過以下頁面支持後續維護、文件整理、WordPress 相容性更新與未來改善：

https://labs.tungchi.jp/support-the-project/

支持完全出於自願。ShinseiFlow 將持續以免費開源外掛的形式提供。

## 授權

ShinseiFlow 以 GPL-2.0-or-later 授權發布。

Copyright © Casper Yeh
