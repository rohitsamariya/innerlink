import { useNavigate } from 'react-router-dom';
import AdminUserList from '../components/AdminUserList';

export default function Users() {
    const navigate = useNavigate();

    return (
        <div className="flex-1 p-4 sm:p-8 overflow-y-auto">
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl sm:text-2xl font-bold text-primary">Users</h2>
                <button
                    onClick={() => navigate('/users/create')}
                    className="px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 transition-opacity rounded-md"
                >
                    + Add User
                </button>
            </div>

            <div className="space-y-8">
                <section>
                    <h3 className="text-lg font-medium text-primary mb-4">All Users</h3>
                    <AdminUserList />
                </section>
            </div>
        </div>
    );
}
