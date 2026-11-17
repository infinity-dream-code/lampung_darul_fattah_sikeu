-- Optional: jika Data Tagihan kosong setelah NO_WA diperbaiki
-- (karena FSTSBolehBayar masih 0), aktifkan tagihan belum lunas:
UPDATE scctbill
SET FSTSBolehBayar = 1
WHERE PAIDST = '0'
  AND (FSTSBolehBayar IS NULL OR FSTSBolehBayar = 0);
