// web/hooks/useMessages.ts
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Conversation, ConversationService } from '@/services/api/ConversationService';

export function useSendMessage(conversationId: number) {
  const queryClient = useQueryClient();
  const conversationService = new ConversationService();
  console.log();
  

  return useMutation({
    mutationFn: (content: string) => conversationService.sendMessage(conversationId, content),
    onMutate: async (content) => {
      // Cancel any outgoing refetches
      await queryClient.cancelQueries({ queryKey: ['conversation', conversationId] });

      // Get current conversation
      const previousConversation = queryClient.getQueryData<Conversation>(['conversation', conversationId]);

      // Optimistically update the conversation
      if (previousConversation) {
        queryClient.setQueryData<Conversation>(['conversation', conversationId], {
          ...previousConversation,
          messages: {
            data: [
              {
                id: Date.now(), // Temporary ID
                content,
                user_id: previousConversation.artist_id,
                created_at: new Date().toISOString(),
                read_at: null,
              },
              ...previousConversation.messages.data,
            ],
          },
        });
      }

      return { previousConversation };
    },
    onError: (err, content, context) => {
      // Revert the optimistic update
      if (context?.previousConversation) {
        queryClient.setQueryData(['conversation', conversationId], context.previousConversation);
      }
    },
    onSettled: () => {
      // Refetch the conversation to ensure we're in sync
      queryClient.invalidateQueries({ queryKey: ['conversation', conversationId] });
    },
  });
}