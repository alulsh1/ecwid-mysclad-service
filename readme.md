СКРИПТЫ

Перенос статуса заказа из МС в Эквид.
orderStatusToEcwid - ставится на вебхук МС - изменение заказа
https://ms-ecwid-reh.devaprix.ru/orderstatustoecwid.php?id=

Перенос закзов из Эквида в МС.
ordertoms - ставится на вебхук Эквида - создание заказа
https://ms-ecwid-reh.devaprix.ru/whecwid.php - хук на событие order.created

Перенос каталога товаров из МС в Эквид.
productstoEcwid - работает по крону хостинга

Перенос остатков товаров отгрузки МС в Эквид.
demandstockstoecwid - ставится на вебхук МС - создание отгрузки
https://ms-ecwid-reh.devaprix.ru/demandstockstoecwid.php?id=

НАСТРОЙКИ

$fieldSync  -  в артикул Ecwid добавляется атрибут для синхронизации товаров. Будет содержать код синхронизации из МС
config.php  - файл с настройкой параметров и доступов

СКОУПЫ для приложения в Эквид
update_catalog, public_storefront, read_catalog, update_customers, read_customers, update_orders, create_catalog, read_store_profile, read_orders, create_customers

ДЕЙСТВИЯ
директории      log, files в головной директории скрипта#   e c w i d - m y s c l a d - s e r v i c e  
 