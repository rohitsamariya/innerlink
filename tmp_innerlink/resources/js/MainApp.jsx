import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Suspense, lazy } from 'react';
import { AuthProvider, useAuth } from './context/AuthContext';
import { EchoProvider } from './context/EchoContext';
import { ThemeProvider } from './context/ThemeContext';
import ProtectedRoute from './components/ProtectedRoute';
import AppLayout from './components/AppLayout';
import Skeleton from './components/Skeleton';

const Login = lazy(() => import('./pages/Login'));
const Dashboard = lazy(() => import('./pages/Dashboard'));
const Groups = lazy(() => import('./pages/Groups'));
const Chat = lazy(() => import('./pages/Chat'));
const Chats = lazy(() => import('./pages/Chats'));
const PrivateChat = lazy(() => import('./pages/PrivateChat'));
const GroupSettings = lazy(() => import('./pages/GroupSettings'));
const Users = lazy(() => import('./pages/Users'));
const CreateUser = lazy(() => import('./pages/CreateUser'));
const Health = lazy(() => import('./pages/Health'));
const UserActivity = lazy(() => import('./pages/UserActivity'));
const UserActivityDetail = lazy(() => import('./pages/UserActivityDetail'));

function PageFallback() {
    return (
        <div className="flex-1 p-4 sm:p-8 space-y-4">
            <Skeleton className="h-8 w-48" />
            <Skeleton className="h-4 w-72" />
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mt-8">
                <Skeleton className="h-28" count={4} />
            </div>
        </div>
    );
}

function ChatFallback() {
    return (
        <div className="flex-1 flex flex-col h-full p-4 space-y-4">
            <Skeleton className="h-12 w-full" />
            <div className="flex-1 space-y-3">
                <Skeleton className="h-16 w-3/4" count={5} />
            </div>
            <Skeleton className="h-12 w-full" />
        </div>
    );
}

function RootRedirect() {
    const { user } = useAuth();
    return <Navigate to={user?.role === 'ADMIN' ? '/dashboard' : '/groups'} replace />;
}

function AppRoutes() {
    return (
        <div className="page-enter">
            <Routes>
                <Route path="/login" element={<Suspense fallback={<div className="min-h-screen flex items-center justify-center"><div className="animate-spin h-8 w-8 border-2 border-primary border-t-transparent rounded-full" /></div>}><Login /></Suspense>} />
                <Route path="/dashboard" element={<ProtectedRoute adminOnly><AppLayout><Suspense fallback={<PageFallback />}><Dashboard /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="/groups" element={<ProtectedRoute><AppLayout><Suspense fallback={<PageFallback />}><Groups /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="/chat/:groupId/settings" element={<ProtectedRoute adminOnly><AppLayout><Suspense fallback={<PageFallback />}><GroupSettings /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="/chat/:groupId" element={<ProtectedRoute><Suspense fallback={<ChatFallback />}><Chat /></Suspense></ProtectedRoute>} />
                <Route path="/users" element={<ProtectedRoute adminOnly><AppLayout><Suspense fallback={<PageFallback />}><Users /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="/users/create" element={<ProtectedRoute adminOnly><AppLayout><Suspense fallback={<PageFallback />}><CreateUser /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="/chats" element={<ProtectedRoute managerPlus><AppLayout><Suspense fallback={<PageFallback />}><Chats /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="/private-chat/:userId" element={<ProtectedRoute managerPlus><Suspense fallback={<ChatFallback />}><PrivateChat /></Suspense></ProtectedRoute>} />
                <Route path="/health" element={<ProtectedRoute adminOnly><AppLayout><Suspense fallback={<PageFallback />}><Health /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="/activity" element={<ProtectedRoute adminOnly><AppLayout><Suspense fallback={<PageFallback />}><UserActivity /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="/activity/:userId" element={<ProtectedRoute adminOnly><AppLayout><Suspense fallback={<PageFallback />}><UserActivityDetail /></Suspense></AppLayout></ProtectedRoute>} />
                <Route path="*" element={<RootRedirect />} />
            </Routes>
        </div>
    );
}

export default function MainApp() {
    return (
        <BrowserRouter>
            <ThemeProvider>
                <AuthProvider>
                    <EchoProvider>
                        <AppRoutes />
                    </EchoProvider>
                </AuthProvider>
            </ThemeProvider>
        </BrowserRouter>
    );
}
