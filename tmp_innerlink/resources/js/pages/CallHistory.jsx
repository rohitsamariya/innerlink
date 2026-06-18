import CallHistoryComponent from '../components/Calling/CallHistory';
import { useAuth } from '../context/AuthContext';

export default function CallHistory() {
    const { user } = useAuth();

    return (
        <div className="flex-1 flex flex-col h-full">
            <div className="px-6 py-4 border-b border-border">
                <h1 className="text-lg font-semibold text-primary">Call History</h1>
                <p className="text-sm text-muted mt-0.5">View your recent calls</p>
            </div>
            <div className="flex-1 overflow-y-auto py-2">
                <CallHistoryComponent userId={user?.id} />
            </div>
        </div>
    );
}
