USE fabrika;
SET NAMES utf8mb4;

-- Расширяем ENUM — добавляем новые статусы проверки
ALTER TABLE stories MODIFY COLUMN status
  ENUM(
    'запланировано',
    'снято',
    'на проверке (редактор)',
    'на проверке (гл.редактор)',
    'проверено',
    'смонтировано',
    'отсмотрено',
    'готово',
    'вышло в эфир',
    'отменено'
  ) NOT NULL DEFAULT 'запланировано';

-- Обновляем старые записи со статусом "на проверке"
UPDATE stories SET status='на проверке (редактор)' WHERE status='на проверке';

SELECT CONCAT('Готово. Обновлено записей: ', ROW_COUNT()) AS result;
