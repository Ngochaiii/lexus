"""
Ảnh trong Car-project KHÔNG phải xe chụp tại Việt Nam — các script import bỏ
qua, giữ nguyên ảnh .webp đã nhập trước đó.

12/10/2026: Car-project commit b15d486 ("update car es", 11/10) thay đè ảnh ES
cùng tên. Bộ màu bạc mang biển số Nhật (品川303 と 30-11), bộ trắng/đen/xám
chụp trước đường chân trời San Diego, bộ xanh dương trên đường núi California
— ảnh báo chí Lexus nước ngoài, không dùng (CLAUDE.md, SalesPhotosTest). Chỉ
nhập bộ màu đồng và ảnh chi tiết chụp ở showroom Việt Nam.
"""

NOT_VN = {
    *(f'es/{colour}-{n}.jpg' for colour in ('trang', 'bac', 'xam', 'xanhduong', 'den') for n in range(1, 6)),
    *(f'es/mau-{colour}.jpg' for colour in ('trang', 'bac', 'xam', 'xanh-duong', 'den')),
}
