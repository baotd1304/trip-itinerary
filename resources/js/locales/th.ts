export default {
    common: {
        cancel: 'ยกเลิก',
        close: 'ปิด',
        delete: 'ลบ',
        saving: 'กำลังบันทึก...',
        uploadingImages: 'กำลังอัปโหลดรูปภาพ...',
        noData: 'ไม่มีข้อมูล',
        none: '—',
        yes: 'ใช่',
        no: 'ไม่ใช่',
        reason: 'เหตุผล',
        note: 'หมายเหตุ',
        sentAt: 'ส่งเมื่อ',
        language: 'ภาษา',
    },

    trip: {
        pageTitle: 'จัดการการเดินทาง',
        listTitle: 'รายการเดินทาง',
        create: 'สร้างการเดินทาง',

        status: {
            pending: 'รอดำเนินการ',
            editing: 'กำลังแก้ไข',
            confirmed: 'ยืนยันแล้ว',
            rejected: 'ถูกปฏิเสธ',
        },

        table: {
            id: 'รหัส',
            advisor: 'ผู้ดูแล',
            driver: 'คนขับรถ',
            origin: 'ต้นทาง',
            destination: 'ปลายทาง',
            day: 'วันที่',
            distance: 'ระยะทาง',
            status: 'สถานะ',
            overnight: 'พักค้างคืน',
            holiday: 'วันหยุด',
            totalFee: 'ค่าธรรมเนียมทั้งหมด',
            actions: 'การดำเนินการ',
        },

        actions: {
            view: 'รายละเอียด',
            edit: 'แก้ไข',
            editRejected: 'แก้ไขและส่งใหม่',
            requestReopen: 'ขอปลดล็อก',
            delete: 'ลบ',
            approveReopen: 'อนุมัติการปลดล็อก',
            rejectReopen: 'ปฏิเสธการปลดล็อก',
            confirm: 'ยืนยัน',
            reject: 'ปฏิเสธ',
            backToView: 'กลับไปดูรายละเอียด',
            submitCreate: 'สร้างการเดินทาง',
            submitUpdate: 'บันทึกและส่ง',
            noneAvailable: 'ไม่มีการดำเนินการสำหรับการเดินทางนี้',
        },

        hints: {
            updateConfirmed:
                'การเดินทางได้รับการยืนยันแล้ว — กรุณาขอปลดล็อกเพื่อแก้ไข',
            updateNoPermission: 'คุณไม่มีสิทธิ์แก้ไขการเดินทางนี้',
            updatePending:
                'การเดินทางอยู่ระหว่างรอการตรวจสอบ — คุณยังสามารถแก้ไขได้',
            updateEditing: 'ปลดล็อกแล้ว — แก้ไขและบันทึกเพื่อส่งใหม่',
            updateRejected: 'การเดินทางถูกปฏิเสธ — แก้ไขและส่งใหม่',
            deleteAllowed: 'ลบการเดินทาง',
            deleteDenied: 'สามารถลบได้ก่อนการเดินทางได้รับการยืนยันเท่านั้น',
            requestReopen: 'ขอให้ผู้ดูแลอนุมัติการปลดล็อกเพื่อแก้ไขการเดินทางนี้',
            approveReopen: 'อนุญาตให้คนขับแก้ไขการเดินทางนี้',
            rejectReopen: 'ปฏิเสธคำขอปลดล็อก',
            confirm: 'ยืนยันการเดินทางนี้',
            reject: 'ส่งเหตุผลการปฏิเสธให้คนขับ',
        },

        dialog: {
            createTitle: 'สร้างการเดินทางใหม่',
            viewTitle: 'รายละเอียดการเดินทาง',
            editTitle: 'แก้ไขการเดินทาง',
            viewDescription:
                'ดูข้อมูลอย่างเดียว ใช้ปุ่มด้านล่างเพื่อดำเนินการ',
        },

        form: {
            advisor: 'ผู้ดูแล',
            selectAdvisor: '-- เลือกผู้ดูแล --',
            driver: 'คนขับรถ',
            selectDriver: '-- เลือกคนขับรถ --',
            day: 'วันที่',
            car: 'รถยนต์',
            selectCar: '-- เลือกรถยนต์ --',
            origin: 'ต้นทาง',
            destination: 'ปลายทาง',
            departureTime: 'เวลาออกเดินทาง',
            arrivalTime: 'เวลาถึงปลายทาง',
            odoStart: 'เลขไมล์เริ่มต้น',
            odoEnd: 'เลขไมล์สิ้นสุด',
            distanceAuto: 'ระยะทาง (อัตโนมัติ)',
            overtime: 'ล่วงเวลา (ชั่วโมง)',
            tollFee: 'ค่าทางด่วน',
            airportFee: 'ค่าธรรมเนียมสนามบิน / ค่าจอดรถ',
            overnight: 'พักค้างคืน',
            holiday: 'วันหยุด',
            note: 'หมายเหตุ',
        },

        detail: {
            generalInfo: 'ข้อมูลการเดินทาง',
            expenses: 'ค่าใช้จ่าย',
            route: 'เส้นทาง',
            totalFee: 'ค่าธรรมเนียมทั้งหมด',
            overtimeHours: '{count} ชั่วโมง',
            distanceKm: '{value} กม.',
        },

        images: {
            label: 'รูปภาพการเดินทาง',
            counter: '{current}/{max} รูป',
            countOnly: '{count} รูป',
            dropHint:
                'คลิกเพื่อเลือกรูปภาพ (JPG, PNG, WEBP · ขนาดไม่เกิน 5MB ต่อรูป)',
            preview: 'ตัวอย่างรูปภาพ',
            empty: 'การเดินทางนี้ไม่มีรูปภาพแนบ',
            alt: 'รูปภาพที่ #{id}',
        },

        banners: {
            rejectedTitle: '{name} ปฏิเสธการเดินทางนี้',
            rejectedEditHint: 'กรุณาอัปเดตข้อมูลและบันทึกเพื่อส่งใหม่',
            reopenApprovedTitle: '{name} อนุมัติคำขอปลดล็อก',
            reopenRejectedTitle: '{name} ปฏิเสธคำขอปลดล็อก',
            reopenPendingTitle: 'คำขอปลดล็อกอยู่ระหว่างรอการตรวจสอบ',
            reviewNote: 'หมายเหตุ',
            noReviewNote: 'ไม่มีหมายเหตุ',
            requestReason: 'เหตุผลที่ส่งคำขอ',
            defaultApproveNote:
                'อนุมัติคำขอปลดล็อกแล้ว กรุณาอัปเดตข้อมูล',
        },

        cell: {
            rejectedBy: '{name} ปฏิเสธ:',
            waitingReopen: 'รอการอนุมัติการปลดล็อก:',
            reopenApproved: 'อนุมัติการปลดล็อกแล้ว:',
            reopenRejected: 'ปฏิเสธการปลดล็อก:',
            fallbackAdvisor: 'ผู้ดูแล',
        },

        warnings: {
            pending:
                'การเดินทางนี้กำลังรอผู้ดูแลตรวจสอบ คุณยังสามารถแก้ไขได้ก่อนการยืนยัน',
            editing:
                'หลังจากบันทึก สถานะการเดินทางจะกลับเป็น "รอดำเนินการ"',
            rejected:
                'หลังจากบันทึก การเดินทางจะถูกส่งกลับไปตรวจสอบอีกครั้ง',
        },

        deleteDialog: {
            title: 'ลบการเดินทาง',
            description: 'การดำเนินการนี้ไม่สามารถย้อนกลับได้',
            confirmText:
                'ต้องการลบการเดินทางหมายเลข #{id} ในวันที่ {day} ({route}) อย่างถาวรหรือไม่?',
        },

        reopenRequest: {
            title: 'ขอปลดล็อกเพื่อแก้ไข',
            description:
                'คำขอจะถูกส่งไปยังผู้ดูแลที่ได้รับมอบหมาย เมื่อได้รับอนุมัติ สถานะการเดินทางจะเปลี่ยนเป็น "กำลังแก้ไข" และคุณจะสามารถอัปเดตข้อมูลได้',
            reasonLabel: 'เหตุผลในการแก้ไข',
            reasonPlaceholder:
                'เช่น เลขไมล์สิ้นสุดไม่ถูกต้อง ควรเป็น 45,320 หรือไม่มีใบเสร็จค่าทางด่วน',
            submit: 'ส่งคำขอ',
        },

        reopenReview: {
            approveTitle: 'อนุมัติคำขอปลดล็อก',
            rejectTitle: 'ปฏิเสธคำขอปลดล็อก',
            approveDescription:
                'การเดินทางจะเปลี่ยนเป็น "กำลังแก้ไข" เพื่อให้คนขับสามารถอัปเดตข้อมูลได้',
            rejectDescription:
                'การเดินทางจะยังคงอยู่ในสถานะ "ยืนยันแล้ว"',
            requesterSaid: '{name} ขอแก้ไขว่า:',
            noteLabel: 'หมายเหตุการตรวจสอบ',
            approvePlaceholder:
                'เช่น อนุมัติแล้ว กรุณาแนบรูปใบเสร็จ',
            rejectPlaceholder:
                'เช่น กรุณาระบุเหตุผลในการปฏิเสธ',
            approveSubmit: 'อนุมัติการปลดล็อก',
            rejectSubmit: 'ปฏิเสธ',
        },

        rejectTrip: {
            title: 'ปฏิเสธการเดินทาง',
            description:
                'สถานะการเดินทางจะเปลี่ยนเป็น "ถูกปฏิเสธ" คนขับสามารถแก้ไขและส่งใหม่ได้',
            reasonLabel: 'เหตุผลในการปฏิเสธ',
            reasonPlaceholder:
                'เช่น เลขไมล์ไม่ตรงกัน กรุณาถ่ายรูปหน้าปัดรถใหม่',
            submit: 'ปฏิเสธการเดินทาง',
        },
    },
} as const;