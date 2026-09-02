import { Banknote } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency } from '@/lib/format';

type Props = {
    open: boolean;
    total: number;
    processing?: boolean;
    onClose: () => void;
    onConfirm: () => void;
};

export default function PosCheckoutDialog({
    open,
    total,
    processing = false,
    onClose,
    onConfirm,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
            <DialogContent className="sm:max-w-md" dir="rtl">
                <DialogHeader>
                    <DialogTitle>إتمام الدفع نقداً</DialogTitle>
                </DialogHeader>

                <div className="py-4 text-center">
                    <p className="text-sm text-muted-foreground">المبلغ المطلوب</p>
                    <p className="text-4xl font-bold text-slate-900">{formatCurrency(total)}</p>
                </div>

                <Button
                    size="lg"
                    className="h-14 w-full gap-2 bg-slate-900 hover:bg-slate-800"
                    disabled={processing}
                    onClick={onConfirm}
                >
                    {processing ? <Spinner /> : <Banknote className="size-5" />}
                    تأكيد استلام النقد
                </Button>

                <DialogFooter>
                    <Button variant="ghost" onClick={onClose} disabled={processing} className="w-full">
                        إلغاء
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
