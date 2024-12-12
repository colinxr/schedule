'use client';

import { useConversations } from '@/hooks/useConversations';
import { useAuth } from '@/contexts/AuthContext';

export default function DashboardPage() {
  const { isAuthenticated, user } = useAuth();
  const { data: conversations, isLoading, error } = useConversations();

  console.log('Auth State:', { isAuthenticated, user });

  if (!isAuthenticated) {
    return <div>Please log in to view conversations</div>;
  }

  if (isLoading) {
    return <div>Loading conversations...</div>;
  }

  if (error) {
    return <div>Error loading conversations</div>;
  }

  return (
    <div>
      <h1>Your Conversations</h1>
      <div className="space-y-4">
        {conversations?.map((conversation) => (
          <div 
            key={conversation.id}
            className="p-4 border rounded-lg shadow-sm hover:shadow-md transition-shadow"
          >
            <div className="flex justify-between items-start">
              <div>
                <h3 className="font-semibold">
                  {conversation.artist.first_name} {conversation.artist.last_name}
                </h3>
                <p className="text-sm text-gray-600">
                  {conversation.details.description}
                </p>
              </div>
              <span className="text-xs text-gray-500">
                {new Date(conversation.last_message_at).toLocaleDateString()}
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
} 